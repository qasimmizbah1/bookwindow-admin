<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrdersImport implements ToModel, WithHeadingRow, SkipsEmptyRows, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    private $processedRows = 0;
    private $insertedOrders = 0;
    private $updatedOrders = 0;
    private $skippedRows = 0;
    private $customErrors = [];
    private $skipReasons = [];
    private $importStartTime;
    private $importId;
    private $orderBuffer = [];
    private $defaultProductName = 'TEST';
    private $defaultProductCreated = false;
    
    public function __construct()
    {
        $this->importStartTime = now();
        $this->importId = 'import_' . uniqid();
        Log::info('=== ORDER IMPORT STARTED ===', [
            'import_id' => $this->importId,
            'start_time' => $this->importStartTime,
            'default_product' => $this->defaultProductName
        ]);
    }
    
    /**
     * Get or create default product
     */
    private function getDefaultProduct()
    {
        $product = Product::firstOrCreate(
            ['name' => $this->defaultProductName],
            [
                'name' => $this->defaultProductName,
                'price' => 0,
                'description' => 'Default product for import',
                'status' => 'active',
            ]
        );
        
        if ($product->wasRecentlyCreated) {
            $this->defaultProductCreated = true;
            Log::info('Created default product', [
                'import_id' => $this->importId,
                'product_id' => $product->id,
                'product_name' => $this->defaultProductName
            ]);
        }
        
        return $product;
    }
    
    public function model(array $row)
    {
        ++$this->processedRows;
        $rowNumber = $this->processedRows;
        $orderId = $row['ordeid'] ?? null;
        $hasProduct = !empty($row['product_name']);
        
        Log::info("Processing row {$rowNumber}", [
            'import_id' => $this->importId,
            'order_id' => $orderId,
            'has_product' => $hasProduct,
            'product_name' => $row['product_name'] ?? 'EMPTY'
        ]);
        
        try {
            // Skip rows without order ID
            if (empty($row['ordeid'])) {
                $this->skippedRows++;
                $errorMsg = "Row {$rowNumber}: Missing order ID";
                $this->customErrors[] = $errorMsg;
                $this->skipReasons[] = [
                    'row' => $rowNumber,
                    'reason' => 'missing_order_id',
                    'data' => $row
                ];
                Log::warning($errorMsg, ['import_id' => $this->importId]);
                return null;
            }
            
            // Skip rows without email
            if (empty($row['email'])) {
                $this->skippedRows++;
                $errorMsg = "Row {$rowNumber}: Missing email";
                $this->customErrors[] = $errorMsg;
                $this->skipReasons[] = [
                    'row' => $rowNumber,
                    'reason' => 'missing_email',
                    'data' => $row
                ];
                Log::warning($errorMsg, ['import_id' => $this->importId]);
                return null;
            }
            
            // Start transaction for this order
            DB::beginTransaction();
            
            $email = strtolower(trim($row['email']));
            
            // 1. Find or create customer
            $pass = bcrypt(Str::random(10));
            
            $customer = Customer::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $row['first_name'] ?? NULL,
                    'last_name' => $row['last_name'] ?? NULL,
                    'phone' => $row['phone'] ?? null,
                    'password' => $pass,
                    'address' => $row['address'] ?? NULL,
                    'address_2' => $row['address_2'] ?? null,
                    'zip_code' => $row['zip_code'] ?? null,
                    'city' => $row['city'] ?? null,
                    'state' => $row['state'] ?? null,
                ]
            );
            
            Log::info("Row {$rowNumber}: Customer processed", [
                'import_id' => $this->importId,
                'customer_id' => $customer->id,
                'is_new' => $customer->wasRecentlyCreated
            ]);
            
            // 2. Find or create product
            $productName = !empty($row['product_name']) ? $row['product_name'] : $this->defaultProductName;
            
            // First try to find by exact name
            $product = Product::where('name', $productName)->first();
            
            // If product not found and it's the default product, create it
            if (!$product && $productName === $this->defaultProductName) {
                $product = $this->getDefaultProduct();
            }
            
            // If product still not found, skip
            if (!$product) {
                DB::rollBack();
                $this->skippedRows++;
                $errorMsg = "Row {$rowNumber}: Product '{$productName}' not found and could not be created";
                $this->customErrors[] = $errorMsg;
                $this->skipReasons[] = [
                    'row' => $rowNumber,
                    'reason' => 'product_not_found',
                    'product_name' => $productName,
                    'data' => $row
                ];
                Log::warning($errorMsg, ['import_id' => $this->importId]);
                return null;
            }
            
            Log::info("Row {$rowNumber}: Product found/created", [
                'import_id' => $this->importId,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'was_default' => $productName === $this->defaultProductName
            ]);
            
            // 3. Check if order exists
            $existingOrder = Order::where('order_number', $row['ordeid'])->first();
            $isUpdate = $existingOrder ? true : false;
            
            // 4. Create or update order
            $order = Order::updateOrCreate(
                ['order_number' => $row['ordeid']],
                [
                    'user_id' => $customer->id,
                    'email' => $email,
                    'subtotal' => $row['subtotal'] ?? 0,
                    'discount_amount' => $row['discount_amount'] ?? 0,
                    'tax_amount' => $row['tax_amount'] ?? 0,
                    'shipping_amount' => $row['shipping_amount'] ?? 0,
                    'total_amount' => $row['total_amount'] ?? 0,
                    'payment_method' => $row['payment_method'] ?? 'unknown',
                    'status' => $row['payment_status'] ?? 'pending',
                    'shipping_method' => $row['shipping_method'] ?? 'standard',
                    'address' => $row['address'] ?? $customer->address,
                    'address_2' => $row['address_2'] ?? $customer->address_2,
                    'customer_phone' => $row['customer_phone'] ?? $customer->phone,
                    'coupon_code' => $row['coupon_code'] ?? null,
                ]
            );
            
            if ($isUpdate) {
                $this->updatedOrders++;
                Log::info("Row {$rowNumber}: Order updated", [
                    'import_id' => $this->importId,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);
            } else {
                $this->insertedOrders++;
                Log::info("Row {$rowNumber}: Order created", [
                    'import_id' => $this->importId,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);
            }
            
            // 5. Create or update order item
            // Use product price if available, otherwise use row price or default 0
            $price = $product->price ?? ($row['price'] ?? 0);
            $quantity = $row['quantity'] ?? 1;
            
            $orderItem = OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ],
                [
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $quantity * $price,
                ]
            );
            
            Log::info("Row {$rowNumber}: Order item saved", [
                'import_id' => $this->importId,
                'order_item_id' => $orderItem->id,
                'product_name' => $product->name,
                'quantity' => $orderItem->quantity,
                'price' => $orderItem->price,
                'total' => $orderItem->total,
                'was_default_product' => $product->name === $this->defaultProductName
            ]);
            
            // Commit transaction
            DB::commit();
            
            Log::info("Row {$rowNumber}: ✅ SUCCESSFULLY IMPORTED", [
                'import_id' => $this->importId,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $customer->id,
                'email' => $order->email,
                'total_amount' => $order->total_amount,
                'status' => $order->status,
                'action' => $isUpdate ? 'updated' : 'created',
                'items' => $order->items()->count()
            ]);
            
            // Only return the order on the first item of each order
            if (!isset($this->orderBuffer[$order->id])) {
                $this->orderBuffer[$order->id] = true;
                return $order;
            }
            
            return null;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->skippedRows++;
            $errorMsg = "Row {$rowNumber}: ERROR - " . $e->getMessage();
            $this->customErrors[] = $errorMsg;
            $this->skipReasons[] = [
                'row' => $rowNumber,
                'reason' => 'exception',
                'error' => $e->getMessage(),
                'data' => $row
            ];
            
            Log::error($errorMsg, [
                'import_id' => $this->importId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Get import statistics
     */
    public function getStats(): array
    {
        return [
            'import_id' => $this->importId,
            'start_time' => $this->importStartTime,
            'end_time' => now(),
            'duration_seconds' => now()->diffInSeconds($this->importStartTime),
            'total_rows_processed' => $this->processedRows,
            'orders_created' => $this->insertedOrders,
            'orders_updated' => $this->updatedOrders,
            'total_orders' => $this->insertedOrders + $this->updatedOrders,
            'rows_skipped' => $this->skippedRows,
            'default_product_created' => $this->defaultProductCreated,
            'success_rate' => $this->processedRows > 0 
                ? round((($this->insertedOrders + $this->updatedOrders) / $this->processedRows) * 100, 2) 
                : 0,
            'skip_reasons' => $this->getSkipReasonSummary(),
            'errors' => $this->customErrors
        ];
    }
    
    /**
     * Get summary of skip reasons
     */
    private function getSkipReasonSummary(): array
    {
        $summary = [];
        foreach ($this->skipReasons as $skip) {
            $key = $skip['reason'] ?? 'unknown';
            if (!isset($summary[$key])) {
                $summary[$key] = 0;
            }
            $summary[$key]++;
        }
        return $summary;
    }
    
    /**
     * Get processed row count
     */
    public function getProcessedRowCount(): int
    {
        return $this->processedRows;
    }
    
    /**
     * Get inserted order count
     */
    public function getInsertedOrdersCount(): int
    {
        return $this->insertedOrders;
    }
    
    /**
     * Get updated order count
     */
    public function getUpdatedOrdersCount(): int
    {
        return $this->updatedOrders;
    }
    
    /**
     * Get skipped row count
     */
    public function getSkippedRowCount(): int
    {
        return $this->skippedRows;
    }
    
    /**
     * Get custom errors
     */
    public function getCustomErrors(): array
    {
        return $this->customErrors;
    }
    
    /**
     * Get detailed skip reasons
     */
    public function getSkipReasons(): array
    {
        return $this->skipReasons;
    }
    
    /**
     * Destructor - log final summary
     */
    public function __destruct()
    {
        $stats = $this->getStats();
        Log::info('=== ORDER IMPORT COMPLETED ===', $stats);
    }
}