<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use App\Imports\OrdersImport;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Group;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = "Shop";

    // Access control: Admin, Vendor, or Team with 'orders' permission
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isAdmin() || $user->isVendor()) return true;
        return $user->hasPermission('orders');
    }

    // Disable the create action globally
    public static function canCreate(): bool
    {
        return false;
    }

    // Show pending order count in navigation
    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->isVendor()) {
            $vendor = auth()->user()->vendor;
            if ($vendor) {
                return (string) static::getModel()::whereHas('items', function ($q) use ($vendor) {
                    $q->where('vendor_id', $vendor->id);
                })->whereIn('status', ['pending', 'processing'])->count();
            }
            return '0';
        }

        return (string) static::getModel()::whereIn('status', ['pending', 'processing'])->count();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->isVendor()) {
            $vendor = auth()->user()->vendor;
            if ($vendor) {
                $query->whereHas('items', function ($q) use ($vendor) {
                    $q->where('vendor_id', $vendor->id);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filter orders based on the header dropdown selection aligned with Import Orders
        $orderStatus = request()->query('order_status');
        if ($orderStatus === 'confirmed') {
            $query->whereIn('status', ['processing', 'order_shipped', 'completed']);
        } elseif ($orderStatus === 'rejected') {
            $query->whereIn('status', ['declined', 'cancelled', 'payment_cancelled']);
        }

        return $query;
    }

        public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(['default' => 1, 'lg' => 12])
                    ->schema([
                        // LEFT COLUMN (Main details - 8 columns on desktop)
                        Group::make([
                            // 1. ORDER ITEMS (Products List & Calculations)
                            Section::make('Order Items')
                                ->icon('heroicon-o-shopping-bag')
                                ->schema([
                                    Placeholder::make('items')
                                        ->label('')
                                        ->content(function ($record) {
                                            if (!$record || !$record->items || $record->items->count() == 0) {
                                                return '<div class="text-center py-8 text-gray-500 dark:text-gray-400">No items found in this order</div>';
                                            }
                                            
                                            $isVendor = auth()->user()?->isVendor() ?? false;
                                            $vendor = auth()->user()?->vendor;

                                            $items = ($isVendor && $vendor)
                                                ? $record->items->where('vendor_id', $vendor->id)
                                                : $record->items;

                                            if ($items->isEmpty()) {
                                                return '<div class="text-center py-8 text-gray-500 dark:text-gray-400">No products belonging to your store found in this order.</div>';
                                            }

                                            $html = '<style>
                                                .bw-table-box {
                                                    border-radius: 8px;
                                                    overflow: hidden;
                                                    border: 1px solid #e5e7eb;
                                                    background: #ffffff;
                                                }
                                                .bw-table-box table {
                                                    width: 100%;
                                                    border-collapse: collapse;
                                                }
                                                .bw-table-box th {
                                                    background: #f9fafb;
                                                    border-bottom: 1px solid #e5e7eb;
                                                    color: #4b5563;
                                                    font-size: 11px;
                                                    font-weight: 600;
                                                    text-transform: uppercase;
                                                    letter-spacing: 0.05em;
                                                    padding: 10px 12px;
                                                }
                                                .bw-table-box td {
                                                    border-bottom: 1px solid #f3f4f6;
                                                    padding: 10px 12px;
                                                    color: #1f2937;
                                                    font-size: 13px;
                                                }
                                                .bw-pname {
                                                    color: #111827;
                                                    font-size: 13px;
                                                    font-weight: 600;
                                                    line-height: 1.4;
                                                }
                                                .bw-pmodel {
                                                    color: #6b7280;
                                                    font-size: 11px;
                                                    font-family: monospace;
                                                    margin-top: 2px;
                                                }
                                                .bw-calc-card {
                                                    margin-top: 14px;
                                                    background: #f9fafb;
                                                    border: 1px solid #e5e7eb;
                                                    border-radius: 8px;
                                                    padding: 14px;
                                                }
                                                .bw-calc-row {
                                                    display: flex;
                                                    justify-content: space-between;
                                                    padding: 6px 0;
                                                    border-bottom: 1px solid #e5e7eb;
                                                    color: #4b5563;
                                                    font-size: 12px;
                                                }
                                                .bw-calc-val {
                                                    font-weight: 600;
                                                    color: #111827;
                                                }
                                                .bw-calc-total {
                                                    display: flex;
                                                    justify-content: space-between;
                                                    padding-top: 10px;
                                                    font-size: 15px;
                                                    font-weight: bold;
                                                    color: #111827;
                                                }
                                                .bw-total-amt {
                                                    color: #059669;
                                                    font-size: 18px;
                                                }

                                                /* DARK MODE ADAPTATION */
                                                .dark .bw-table-box {
                                                    border-color: rgba(255, 255, 255, 0.08);
                                                    background: rgba(0, 0, 0, 0.25);
                                                }
                                                .dark .bw-table-box th {
                                                    background: rgba(255, 255, 255, 0.04);
                                                    border-color: rgba(255, 255, 255, 0.08);
                                                    color: #9ca3af;
                                                }
                                                .dark .bw-table-box td {
                                                    border-color: rgba(255, 255, 255, 0.05);
                                                    color: #f3f4f6;
                                                }
                                                .dark .bw-pname {
                                                    color: #f9fafb;
                                                }
                                                .dark .bw-pmodel {
                                                    color: #9ca3af;
                                                }
                                                .dark .bw-calc-card {
                                                    background: rgba(255, 255, 255, 0.03);
                                                    border-color: rgba(255, 255, 255, 0.08);
                                                }
                                                .dark .bw-calc-row {
                                                    border-color: rgba(255, 255, 255, 0.06);
                                                    color: #9ca3af;
                                                }
                                                .dark .bw-calc-val {
                                                    color: #f3f4f6;
                                                }
                                                .dark .bw-calc-total {
                                                    color: #f3f4f6;
                                                }
                                                .dark .bw-total-amt {
                                                    color: #10b981;
                                                }
                                            </style>';

                                            $html .= '<div class="bw-table-box">
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th style="text-align: center; width: 35px;">#</th>
                                                            <th style="text-align: left;">Product</th>
                                                            <th style="text-align: center; width: 60px;">Qty</th>
                                                            <th style="text-align: right; width: 90px;">Price</th>
                                                            <th style="text-align: right; width: 100px;">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>';
                                            
                                            $rawItemsTotal = $items->sum(function ($item) {
                                                return ($item->quantity ?? 1) * ($item->price ?? 0);
                                            });

                                            // Handle legacy orders where coupon discount was mistakenly deducted from line item prices
                                            $isLegacyDiscounted = ($record && (float)($record->discount_amount ?? 0) > 0 && $rawItemsTotal > 0 && abs(($rawItemsTotal + (float)$record->discount_amount) - (float)($record->subtotal ?? 0)) < 0.05);
                                            $subtotalRatio = ($isLegacyDiscounted && $rawItemsTotal > 0) ? ((float)$record->subtotal / $rawItemsTotal) : 1.0;

                                            $counter = 1;
                                            foreach ($items as $item) {
                                                $product = $item->product;
                                                $productName = $product ? $product->name : ($item->product_name ?? 'Product #' . $item->product_id);
                                                $productModel = $product?->model ?? '';
                                                $unitPrice = $isLegacyDiscounted ? round(($item->price ?? 0) * $subtotalRatio, 2) : ($item->price ?? 0);
                                                $subtotal = ($item->quantity ?? 1) * $unitPrice;

                                                $html .= '<tr>
                                                    <td style="text-align: center; color: #9ca3af; font-size: 12px;">' . $counter . '</td>
                                                    <td>
                                                        <div class="bw-pname">' . e($productName) . '</div>
                                                        ' . ($productModel ? '<div class="bw-pmodel">' . e($productModel) . '</div>' : '') . '
                                                    </td>
                                                    <td style="text-align: center; font-weight: 600;">' . ($item->quantity ?? 1) . '</td>
                                                    <td style="text-align: right; white-space: nowrap;">' . format_currency($unitPrice, 2) . '</td>
                                                    <td style="text-align: right; font-weight: bold; white-space: nowrap;">' . format_currency($subtotal, 2) . '</td>
                                                </tr>';
                                                $counter++;
                                            }
                                            
                                            $html .= '</tbody></table></div>';
                                            
                                            $totalItems = $items->sum('quantity');
                                            $displaySubtotal = ($record && $record->subtotal && (float)$record->subtotal > 0)
                                                ? (float)$record->subtotal
                                                : $rawItemsTotal;
                                            $totalAmount = $displaySubtotal;
                                            
                                            if ($isVendor) {
                                                $commissionRate = $vendor->commission_rate;
                                                $gstRate = $vendor->commission_gst_rate;
                                                $commissionAmount = $vendor->calculateCommissionFee($totalAmount);
                                                $gstAmount = $vendor->calculateCommissionGst($totalAmount);
                                                $totalDeduction = $vendor->calculateTotalDeduction($totalAmount);
                                                $netEarnings = $vendor->calculateVendorPayout($totalAmount);

                                                $html .= '<div class="bw-calc-card">
                                                    <div style="max-width: 300px; margin-left: auto;">
                                                        <div class="bw-calc-row">
                                                            <span>Your Items Total:</span>
                                                            <span class="bw-calc-val">' . $totalItems . ' item(s)</span>
                                                        </div>
                                                        <div class="bw-calc-row">
                                                            <span>Gross Sales:</span>
                                                            <span class="bw-calc-val">' . format_currency($totalAmount, 2) . '</span>
                                                        </div>
                                                        <div class="bw-calc-row" style="color: #d97706;">
                                                            <span>Platform Fee (' . $commissionRate . '%):</span>
                                                            <span style="font-weight: 600;">-' . format_currency($commissionAmount, 2) . '</span>
                                                        </div>
                                                        <div class="bw-calc-row" style="color: #d97706;">
                                                            <span>GST on Fee (' . $gstRate . '%):</span>
                                                            <span style="font-weight: 600;">-' . format_currency($gstAmount, 2) . '</span>
                                                        </div>
                                                        <div class="bw-calc-row" style="color: #dc2626;">
                                                            <span>Total Charges:</span>
                                                            <span style="font-weight: bold;">-' . format_currency($totalDeduction, 2) . '</span>
                                                        </div>
                                                        <div class="bw-calc-total">
                                                            <span>Net Payout:</span>
                                                            <span class="bw-total-amt">' . format_currency($netEarnings, 2) . '</span>
                                                        </div>
                                                    </div>
                                                </div>';
                                            } else {
                                                $html .= '<div class="bw-calc-card">
                                                    <div style="max-width: 300px; margin-left: auto;">
                                                        <div class="bw-calc-row">
                                                            <span>Total Items:</span>
                                                            <span class="bw-calc-val">' . $totalItems . '</span>
                                                        </div>
                                                        <div class="bw-calc-row">
                                                            <span>Items Subtotal:</span>
                                                            <span class="bw-calc-val">' . format_currency($displaySubtotal, 2) . '</span>
                                                        </div>
                                                        <div class="bw-calc-row">
                                                            <span>Shipping:</span>
                                                            <span class="bw-calc-val">' . format_currency($record->shipping_amount ?? 0, 2) . '</span>
                                                        </div>';

                                                if ($record && (strtolower($record->payment_method ?? '') === 'cod' || ($record->delivery_amount ?? 0) > 0)) {
                                                    $html .= '<div class="bw-calc-row">
                                                        <span>COD Fee:</span>
                                                        <span class="bw-calc-val">' . format_currency($record->delivery_amount ?? 0, 2) . '</span>
                                                    </div>';
                                                }

                                                $html .= '<div class="bw-calc-row" style="color: #d97706;">
                                                            <span>Discount:</span>
                                                            <span style="font-weight: 600;">-' . format_currency($record->discount_amount ?? 0, 2) . '</span>
                                                        </div>
                                                        <div class="bw-calc-row">
                                                            <span>Tax:</span>
                                                            <span class="bw-calc-val">' . format_currency($record->tax_amount ?? 0, 2) . '</span>
                                                        </div>
                                                        <div class="bw-calc-total">
                                                            <span>Grand Total:</span>
                                                            <span class="bw-total-amt">' . format_currency($record->total_amount ?? 0, 2) . '</span>
                                                        </div>
                                                    </div>
                                                </div>';
                                            }
                                            
                                            return new \Illuminate\Support\HtmlString($html);
                                        })
                                        ->columnSpanFull(),
                                ]),

                            // 2. CUSTOMER INFORMATION
                            Section::make('Customer Information')
                                ->icon('heroicon-o-user')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('first_name')
                                                ->label('First Name')
                                                ->readOnly()
                                                ->maxLength(100),

                                            TextInput::make('last_name')
                                                ->label('Last Name')
                                                ->readOnly()
                                                ->maxLength(100),

                                            TextInput::make('email')
                                                ->label('Email')
                                                ->email()
                                                ->readOnly()
                                                ->maxLength(150),

                                            TextInput::make('customer_phone')
                                                ->label('Phone Number / Mobile')
                                                ->tel()
                                                ->readOnly()
                                                ->placeholder('e.g. 9876543210')
                                                ->maxLength(20),
                                        ]),
                                ]),

                            // 3. SHIPPING ADDRESS
                            Section::make('Shipping Address')
                                ->icon('heroicon-o-map-pin')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('address')
                                                ->label('Address Line 1')
                                                ->columnSpanFull(),

                                            TextInput::make('address_2')
                                                ->label('Address Line 2')
                                                ->columnSpanFull(),

                                            TextInput::make('city')
                                                ->label('City'),

                                            TextInput::make('district')
                                                ->label('District'),

                                            TextInput::make('state')
                                                ->label('State/Province'),

                                            TextInput::make('zip_code')
                                                ->label('Postal Code / PIN'),

                                            TextInput::make('country')
                                                ->label('Country')
                                                ->default('India'),
                                        ]),
                                ]),

                            // 4. ADMIN REMARKS & INTERNAL COMMENTS
                            Section::make('Admin Remarks & Internal Comments')
                                ->description('Internal staff comments and order remarks (not visible to customer).')
                                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                                ->schema([
                                    Textarea::make('admin_remark')
                                        ->label('Order Remark / Comment')
                                        ->placeholder('Add internal staff notes or comments about this order...')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->columnSpan(['default' => 12, 'lg' => 8]),

                        // RIGHT COLUMN (Sidebar - 4 columns on desktop)
                        Group::make([
                            // 1. CALL VERIFICATION (Task 1)
                            Section::make('Call Verification')
                                ->description('Telecalling confirmation separate from order status.')
                                ->icon('heroicon-o-phone')
                                ->schema([
                                    Placeholder::make('quick_call_link')
                                        ->label('Quick Dial')
                                        ->content(function ($record) {
                                            if (!$record || empty($record->customer_phone)) {
                                                return new \Illuminate\Support\HtmlString('<span style="font-size:12px; color:#9ca3af;">No phone number available</span>');
                                            }
                                            $rawPhone = $record->customer_phone;
                                            $cleanPhone = preg_replace('/[^0-9+]/', '', $rawPhone);
                                            $displayPhone = e(Order::formatPhoneNumber($rawPhone));
                                            $telHref = 'tel:' . e($cleanPhone);

                                            return new \Illuminate\Support\HtmlString('
                                                <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:#f0fdf4; border-radius:8px;" class="bw-quick-dial-box">
                                                    <style>
                                                        .dark .bw-quick-dial-box { background: rgba(6, 78, 59, 0.25) !important; }
                                                        .bw-call-btn:hover { background-color: #059669 !important; transform: scale(1.02); }
                                                        .bw-phone-link:hover { text-decoration: underline !important; }
                                                    </style>
                                                    <div>
                                                        <div style="font-size:11px; font-weight:600; color:#047857; text-transform:uppercase; letter-spacing:0.5px;">Customer Mobile</div>
                                                        <a href="' . $telHref . '" class="bw-phone-link" style="font-size:15px; font-weight:700; color:#10b981; text-decoration:none;">' . $displayPhone . '</a>
                                                    </div>
                                                    <a href="' . $telHref . '" class="bw-call-btn" style="display:inline-flex; align-items:center; gap:6px; background-color:#10b981; color:#ffffff; font-size:12px; font-weight:600; padding:7px 14px; border-radius:6px; text-decoration:none; transition: all 0.15s ease; cursor:pointer;">
                                                        <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                        </svg>
                                                        <span>Click to Call</span>
                                                    </a>
                                                </div>
                                            ');
                                        }),

                                    Select::make('call_status')
                                        ->label('Call Status')
                                        ->options(Order::getCallStatuses())
                                        ->default('pending')
                                        ->required()
                                        ->native(false),

                                    Textarea::make('call_notes')
                                        ->label('Call Notes / Remarks')
                                        ->placeholder('e.g. Customer ne bola dispatch kardo...')
                                        ->rows(2),

                                    Placeholder::make('called_at_display')
                                        ->label('Last Called At')
                                        ->content(fn ($record) => $record?->called_at ? \Illuminate\Support\Carbon::parse($record->called_at)->format('M d, Y h:i A') : 'Not called yet')
                                        ->visible(fn ($record) => $record && $record->called_at),
                                ]),

                            // 2. ORDER INFORMATION & STATUS
                            Section::make('Order Information')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->schema([
                                    Placeholder::make('order_number')
                                        ->label('Order ID')
                                        ->content(function ($record) {
                                            return $record->order_number ?? 'N/A';
                                        }),
                                    
                                    Select::make('status')
                                        ->label('Order Status')
                                        ->options([
                                            "new"=>"New",
                                            "pending"=>"Pending",
                                            "processing"=>"Processing",
                                            'order_shipped'=>"Order Shipped",
                                            "completed"=>"Order Completed/Delivered",
                                            "cancelled"=>"Order Cancelled",
                                            "declined"=>"Order Declined",
                                            "refunded"=>"Refunded",
                                        ])
                                        ->required()
                                        ->live()
                                        ->native(false),

                                    Placeholder::make('cancelled_by_display')
                                        ->label('Cancelled By')
                                        ->visible(fn ($record, Forms\Get $get) => (in_array($get('status'), ['cancelled', 'declined', 'payment_cancelled']) || str_contains((string)$get('status'), 'cancel')) && !empty($record?->cancelled_by))
                                        ->content(function ($record) {
                                            return match($record?->cancelled_by) {
                                                'customer' => 'Customer (From User Account)',
                                                'admin' => 'Admin / Store Staff',
                                                'payment_failed' => 'Payment Cancelled / Failed at Gateway',
                                                default => ucfirst($record?->cancelled_by ?? 'N/A'),
                                            };
                                        }),

                                    Select::make('cancellation_reason_preset')
                                        ->label('Cancellation Reason Preset')
                                        ->options([
                                            'Customer requested cancellation' => 'Customer requested cancellation',
                                            'Product out of stock' => 'Product out of stock',
                                            'Customer unreachable on phone' => 'Customer unreachable on phone',
                                            'Incorrect or incomplete delivery address' => 'Incorrect or incomplete delivery address',
                                            'Payment issue / Unpaid COD' => 'Payment issue / Unpaid COD',
                                            'Duplicate order placed by mistake' => 'Duplicate order placed by mistake',
                                            'Other reason' => 'Other reason (Specify in remarks below)',
                                        ])
                                        ->visible(fn (Forms\Get $get) => in_array($get('status'), ['cancelled', 'declined']))
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                                            if ($state && $state !== 'Other reason') {
                                                $set('cancellation_reason', $state);
                                            }
                                        })
                                        ->dehydrated(false),

                                    Textarea::make('cancellation_reason')
                                        ->label('Cancellation Reason / Remarks')
                                        ->placeholder('Enter detailed reason or remark for order cancellation...')
                                        ->visible(fn (Forms\Get $get) => in_array($get('status'), ['cancelled', 'declined']))
                                        ->required(fn (Forms\Get $get) => in_array($get('status'), ['cancelled', 'declined']))
                                        ->rows(2)
                                        ->columnSpanFull(),

                                    TextInput::make('courier_partner')
                                        ->label('Courier Partner Name')
                                        ->placeholder('Enter Courier Partner Name (e.g. DTDC, India Post)')
                                        ->live()
                                        ->visible(fn (Forms\Get $get) => in_array($get('status'), ['order_shipped', 'completed'])),

                                    TextInput::make('tracking_id')
                                        ->label('Tracking ID')
                                        ->placeholder('e.g. DTDC12345678, EK123456789IN')
                                        ->live()
                                        ->visible(fn (Forms\Get $get) => in_array($get('status'), ['order_shipped', 'completed']))
                                        ->required(fn (Forms\Get $get) => $get('status') === 'order_shipped'),

                                    Placeholder::make('payment_method')
                                        ->label('Payment Method')
                                        ->content(function ($record) {
                                            $methods = [
                                                "cod"=>"Cash on Delivery",
                                                "card"=>"Credit Card",
                                                "razorpay"=>"Razorpay"
                                            ];
                                            return $methods[$record->payment_method] ?? $record->payment_method ?? 'N/A';
                                        }),
                                    
                                    Select::make('payment_status')
                                        ->label('Payment Status')
                                        ->options([
                                            "pending"=>"Pending",
                                            "paid"=>"Paid",
                                            "failed"=>"Failed",
                                            "refunded"=>"Refunded",
                                        ])
                                        ->required()
                                        ->native(false),
                                    
                                    Placeholder::make('razorpay_order_id')
                                        ->label('Razorpay Order ID')
                                        ->content(function ($record) {
                                            return $record->razorpay_order_id ?? 'N/A';
                                        })
                                        ->visible(fn ($record) => $record && $record->razorpay_order_id),
                                    
                                    Placeholder::make('razorpay_payment_id')
                                        ->label('Razorpay Payment ID')
                                        ->content(function ($record) {
                                            return $record->razorpay_payment_id ?? 'N/A';
                                        })
                                        ->visible(fn ($record) => $record && $record->razorpay_payment_id),
                                ]),

                            // 3. ADDITIONAL INFORMATION (Collapsible)
                            Section::make('Additional Information')
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    Placeholder::make('shipping_method')
                                        ->label('Shipping Method')
                                        ->content(function ($record) {
                                            return $record->shipping_method ?? 'N/A';
                                        }),
                                    
                                    Placeholder::make('coupon_code')
                                        ->label('Coupon Code')
                                        ->content(function ($record) {
                                            return $record->coupon_code ?? 'N/A';
                                        }),
                                    
                                    Placeholder::make('notes')
                                        ->label('Order Notes (Customer)')
                                        ->content(function ($record) {
                                            return $record->notes ?? 'No notes';
                                        })
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->columnSpan(['default' => 12, 'lg' => 4]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable(),
                
                // Tables\Columns\TextColumn::make('order_number')
                //     ->label('Order ID')
                //     ->sortable()
                //     ->searchable(),
                
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Customer Name')
                    ->getStateUsing(function ($record) {
                        return ($record->first_name ?? '') . ' ' . ($record->last_name ?? '');
                    })
                    ->sortable(['first_name', 'last_name'])
                    ->searchable(['first_name', 'last_name'])
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->formatStateUsing(fn ($state) => Order::formatPhoneNumber($state))
                    ->searchable(query: function (Builder $query, string $search) {
                        $clean = preg_replace('/\D/', '', $search);
                        if (str_starts_with($clean, '91') && strlen($clean) > 10) {
                            $clean = substr($clean, 2);
                        }
                        $clean = substr($clean, -10);
                        return $query->where(function ($q) use ($search, $clean) {
                            $q->where('customer_phone', 'like', "%{$search}%");
                            if (!empty($clean)) {
                                $q->orWhere('customer_phone', 'like', "%{$clean}%");
                            }
                        });
                    })
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('call_status')
                    ->label('Call Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => fn ($state) => in_array($state, ['cancelled_by_customer', 'wrong_number']),
                        'amber' => 'not_picked',
                        'info' => 'call_again',
                    ])
                    ->icons([
                        'heroicon-o-phone-arrow-up-right' => 'pending',
                        'heroicon-o-check-circle' => 'confirmed',
                        'heroicon-o-phone-x-mark' => 'not_picked',
                        'heroicon-o-clock' => 'call_again',
                        'heroicon-o-x-circle' => 'cancelled_by_customer',
                        'heroicon-o-no-symbol' => 'wrong_number',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'confirmed' => 'Confirmed',
                        'not_picked' => 'Not Picked',
                        'call_again' => 'Call Again',
                        'cancelled_by_customer' => 'Cancelled',
                        'wrong_number' => 'Wrong No.',
                        default => 'To Call',
                    })
                    ->description(fn ($record) => $record->call_notes ? Str::limit($record->call_notes, 25) : null)
                    ->tooltip(fn ($record) => $record->call_notes ? "Call Notes: {$record->call_notes}" : null)
                    ->sortable()
                    ->toggleable(),
                
                // Tables\Columns\TextColumn::make('city')
                //     ->label('City')
                //     ->toggleable(),
                
                // Tables\Columns\TextColumn::make('country')
                //     ->label('Country')
                //     ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->colors([
                        'gray' => 'new',
                        'warning' => 'pending',
                        'info' => 'processing',
                        'primary' => 'order_shipped',
                        'success' => 'completed',
                        'danger' => 'declined',
                        'danger' => 'cancelled',
                        'purple' => 'refunded',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'new',
                        'heroicon-o-exclamation-circle' => 'pending',
                        'heroicon-o-arrow-path' => 'processing',
                        'heroicon-o-truck' => 'order_shipped',
                        'heroicon-o-check-circle' => 'completed',
                        'heroicon-o-x-circle' => 'declined',
                        'heroicon-o-x-mark' => 'cancelled',
                        'heroicon-o-arrow-uturn-left' => 'refunded',
                    ])
                    ->description(function ($record) {
                        if ((in_array($record->status, ['cancelled', 'declined', 'payment_cancelled']) || str_contains((string)$record->status, 'cancel')) && !empty($record->cancellation_reason)) {
                            $prefix = $record->cancelled_by === 'customer' 
                                ? 'By User: ' 
                                : ($record->cancelled_by === 'admin' 
                                    ? 'By Admin: ' 
                                    : ($record->cancelled_by === 'payment_failed' ? 'Payment Failed: ' : 'Reason: '));
                            return $prefix . Str::limit($record->cancellation_reason, 35);
                        }
                        if (in_array($record->status, ['order_shipped', 'Shipped', 'completed', 'Complete']) && (!empty($record->tracking_id) || !empty($record->courier_partner))) {
                            $parts = array_filter([$record->courier_partner, $record->tracking_id]);
                            return implode(' - ', $parts);
                        }
                        return null;
                    })
                    ->tooltip(function ($record) {
                        if ((in_array($record->status, ['cancelled', 'declined', 'payment_cancelled']) || str_contains((string)$record->status, 'cancel')) && !empty($record->cancellation_reason)) {
                            $prefix = $record->cancelled_by === 'customer' 
                                ? 'Cancelled by Customer: ' 
                                : ($record->cancelled_by === 'admin' 
                                    ? 'Cancelled by Admin: ' 
                                    : ($record->cancelled_by === 'payment_failed' ? 'Payment Failed / Cancelled: ' : 'Reason: '));
                            return $prefix . $record->cancellation_reason;
                        }
                        if (in_array($record->status, ['order_shipped', 'Shipped', 'completed', 'Complete']) && (!empty($record->tracking_id) || !empty($record->courier_partner))) {
                            return 'Courier: ' . ($record->courier_partner ?? 'N/A') . ' | Tracking: ' . ($record->tracking_id ?? 'N/A');
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('courier_partner')
                    ->label('Courier')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tracking_id')
                    ->label('Tracking ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Tracking ID copied')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('admin_remark')
                    ->label('Admin Remark')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->admin_remark)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->colors([
                        'success' => 'cod',
                        'primary' => 'card',
                        'warning' => 'razorpay',
                    ]),
                
                // Tables\Columns\TextColumn::make('payment_status')
                //     ->label('Payment Status')
                //     ->badge()
                //     ->colors([
                //         'warning' => 'pending',
                //         'success' => 'paid',
                //         'danger' => 'failed',
                //         'info' => 'refunded',
                //     ]),
                
                // Tables\Columns\TextColumn::make('subtotal')
                //     ->label('Subtotal')
                //     ->money('INR')
                //     ->sortable(),
                
                Tables\Columns\TextColumn::make('vendor_items_count')
                    ->label('Your Items')
                    ->getStateUsing(function ($record) {
                        $vendor = auth()->user()?->vendor;
                        if (!$vendor) return '0';
                        return $record->items->where('vendor_id', $vendor->id)->sum('quantity') . ' item(s)';
                    })
                    ->visible(fn () => auth()->user()?->isVendor() ?? false),

                Tables\Columns\TextColumn::make('vendor_earnings')
                    ->label('Your Net Payout')
                    ->getStateUsing(function ($record) {
                        $vendor = auth()->user()?->vendor;
                        if (!$vendor) return '₹0.00';
                        $gross = $record->items->where('vendor_id', $vendor->id)->sum(function ($item) {
                            return ($item->quantity ?? 1) * ($item->price ?? 0);
                        });
                        $net = $vendor->calculateVendorPayout($gross);
                        return format_currency($net, 2);
                    })
                    ->description(function ($record) {
                        $vendor = auth()->user()?->vendor;
                        if (!$vendor) return '';
                        return "after {$vendor->commission_rate}% fee + {$vendor->commission_gst_rate}% GST";
                    })
                    ->badge()
                    ->color('success')
                    ->visible(fn () => auth()->user()?->isVendor() ?? false),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money()
                    ->sortable()
                    ->searchable()
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()->money(),
                    ]),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                // Quick Status Dropdown aligned with Import Orders
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('filterAllOrders')
                        ->label('All Orders')
                        ->icon('heroicon-o-bars-3')
                        ->color('gray')
                        ->url(fn () => route('filament.admin.resources.orders.index')),

                    Tables\Actions\Action::make('filterConfirmedOrders')
                        ->label('Order Confirmed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->url(fn () => route('filament.admin.resources.orders.index', ['order_status' => 'confirmed'])),

                    Tables\Actions\Action::make('filterRejectedOrders')
                        ->label('Rejected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->url(fn () => route('filament.admin.resources.orders.index', ['order_status' => 'rejected'])),
                ])
                ->label(function () {
                    $status = request()->query('order_status');
                    if ($status === 'confirmed') return 'Order Confirmed';
                    if ($status === 'rejected') return 'Rejected';
                    return 'Order Status';
                })
                ->icon('heroicon-m-chevron-down')
                ->color(function () {
                    $status = request()->query('order_status');
                    if ($status === 'confirmed') return 'success';
                    if ($status === 'rejected') return 'danger';
                    return 'gray';
                })
                ->button(),

                // Only import action for admin
                Action::make('import')
                    ->label('Import Orders')
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                    ->action(function (array $data) {
                        $file = storage_path('app/public/' . $data['file']);
                        if (!file_exists($file)) {
                            throw new \Exception("File not found: " . $file);
                        }

                        Excel::import(new OrdersImport(), $file);
                    })
                    ->form([
                        FileUpload::make('file')
                            ->label('CSV File')
                            ->required()
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                '.csv',
                            ]),
                    ]),
            ])
            ->filters([
                Tables\Filters\Filter::make('razorpay_pending')
                    ->label('Razorpay Pending Payments')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('payment_method', 'razorpay')
                        ->whereIn('payment_status', ['payment_pending', 'created'])
                        ->whereNotNull('razorpay_order_id')
                        ->where('razorpay_order_id', '!=', '')
                    ),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        "new"=>"New",
                        "pending"=>"Pending",
                        "processing"=>"Processing",
                        "order_shipped"=>"Order Shipped",
                        "completed"=>"Completed",
                        "declined"=>"Declined",
                        "cancelled"=>"Cancelled",
                        "refunded"=>"Refunded",
                    ]),
                
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        "pending"=>"Pending",
                        "paid"=>"Paid",
                        "failed"=>"Failed",
                        "refunded"=>"Refunded",
                    ]),
                
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        "cod"=>"Cash on Delivery",
                        "card"=>"Credit Card",
                        "razorpay"=>"Razorpay",
                    ]),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DateTimePicker::make('created_from'),
                        DateTimePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                
                Tables\Filters\SelectFilter::make('call_status')
                    ->label('Call Status')
                    ->options(Order::getCallStatuses()),

                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View'),
                    Tables\Actions\EditAction::make()
                        ->label('Edit / Fulfill'),
                    Tables\Actions\Action::make('printInvoice')
                        ->label('Print Slip')
                        ->icon('heroicon-o-printer')
                        ->color('primary')
                        ->url(fn ($record) => "javascript:window.open('" . route('orders.print', $record->id) . "', 'Print', 'width=800,height=600'); void(0);"),
                    Tables\Actions\DeleteAction::make()
                        ->label('Delete')
                        ->visible(fn () => auth()->user()?->isAdmin() ?? false),

                    // Quick action to update Call Status
                    Tables\Actions\Action::make('updateCallStatus')
                        ->label('Call Verification')
                        ->icon('heroicon-o-phone')
                        ->color('success')
                        ->mountUsing(function (Forms\ComponentContainer $form, $record) {
                            $form->fill([
                                'call_status' => $record->call_status ?? 'pending',
                                'call_notes' => $record->call_notes,
                            ]);
                        })
                        ->form([
                            Placeholder::make('customer_contact')
                                ->label('Customer Phone')
                                ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                    '<div class="text-base">📞 <a href="tel:' . e($record->customer_phone) . '" class="text-emerald-600 font-bold hover:underline">' . e($record->customer_phone) . '</a> <span class="text-gray-500 font-normal">(' . e($record->full_name) . ')</span></div>'
                                )),
                            Select::make('call_status')
                                ->label('Call Status')
                                ->options(Order::getCallStatuses())
                                ->required()
                                ->native(false),
                            Textarea::make('call_notes')
                                ->label('Call Notes / Remarks')
                                ->placeholder('Add notes from the call with customer...')
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            if ($data['call_status'] !== 'pending' && empty($record->called_at)) {
                                $data['called_at'] = now();
                            }
                            $record->update($data);

                            \Filament\Notifications\Notification::make()
                                ->title('Call status updated successfully')
                                ->success()
                                ->send();
                        }),

                    // Quick action to edit shipping address & phone
                    Tables\Actions\Action::make('editAddress')
                        ->label('Edit Address & Phone')
                        ->icon('heroicon-o-map-pin')
                        ->color('warning')
                        ->mountUsing(function (Forms\ComponentContainer $form, $record) {
                            $form->fill([
                                'first_name' => $record->first_name,
                                'last_name' => $record->last_name,
                                'customer_phone' => $record->customer_phone,
                                'address' => $record->address,
                                'address_2' => $record->address_2,
                                'city' => $record->city,
                                'state' => $record->state,
                                'zip_code' => $record->zip_code,
                                'country' => $record->country ?? 'India',
                            ]);
                        })
                        ->form([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('first_name')->label('First Name')->maxLength(100),
                                    TextInput::make('last_name')->label('Last Name')->maxLength(100),
                                    TextInput::make('customer_phone')->label('Phone Number / Mobile')->tel()->maxLength(20)->columnSpanFull(),
                                    TextInput::make('address')->label('Address Line 1')->columnSpanFull(),
                                    TextInput::make('address_2')->label('Address Line 2')->columnSpanFull(),
                                    TextInput::make('city')->label('City'),
                                    TextInput::make('state')->label('State/Province'),
                                    TextInput::make('zip_code')->label('Postal Code / PIN'),
                                    TextInput::make('country')->label('Country')->default('India'),
                                ]),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update($data);

                            \Filament\Notifications\Notification::make()
                                ->title('Customer address & phone updated successfully')
                                ->success()
                                ->send();
                        }),

                    // Custom action to update status quickly
                    Tables\Actions\Action::make('updateStatus')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->mountUsing(function (Forms\ComponentContainer $form, $record) {
                            $form->fill([
                                'status' => $record->status,
                                'tracking_id' => $record->tracking_id,
                                'courier_partner' => $record->courier_partner,
                                'cancellation_reason' => $record->cancellation_reason,
                                'admin_remark' => $record->admin_remark,
                            ]);
                        })
                        ->form([
                            Select::make('status')
                                ->options([
                                    "new"=>"New",
                                    "pending"=>"Pending",
                                    "processing"=>"Processing",
                                    "order_shipped"=>"Order Shipped",
                                    "completed"=>"Completed",
                                    "declined"=>"Declined",
                                    "cancelled"=>"Cancelled",
                                    "refunded"=>"Refunded",
                                ])
                                ->required()
                                ->live(),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    TextInput::make('tracking_id')
                                        ->label('Tracking ID')
                                        ->placeholder('e.g. DTDC12345678')
                                        ->visible(fn (Forms\Get $get) => in_array($get('status'), ['order_shipped', 'completed'])),

                                    TextInput::make('courier_partner')
                                        ->label('Courier Partner Name')
                                        ->placeholder('Enter Courier Partner Name')
                                        ->visible(fn (Forms\Get $get) => in_array($get('status'), ['order_shipped', 'completed'])),
                                ])
                                ->visible(fn (Forms\Get $get) => in_array($get('status'), ['order_shipped', 'completed'])),

                            Select::make('cancellation_reason_preset')
                                ->label('Cancellation Reason Preset')
                                ->options([
                                    'Customer requested cancellation' => 'Customer requested cancellation',
                                    'Product out of stock' => 'Product out of stock',
                                    'Customer unreachable on phone' => 'Customer unreachable on phone',
                                    'Incorrect or incomplete delivery address' => 'Incorrect or incomplete delivery address',
                                    'Payment issue / Unpaid COD' => 'Payment issue / Unpaid COD',
                                    'Duplicate order placed by mistake' => 'Duplicate order placed by mistake',
                                    'Other reason' => 'Other reason (Specify in remarks below)',
                                ])
                                ->visible(fn (Forms\Get $get) => in_array($get('status'), ['cancelled', 'declined']))
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    if ($state && $state !== 'Other reason') {
                                        $set('cancellation_reason', $state);
                                    }
                                })
                                ->dehydrated(false),

                            Textarea::make('cancellation_reason')
                                ->label('Cancellation Reason / Remark')
                                ->placeholder('Enter reason why order is cancelled...')
                                ->visible(fn (Forms\Get $get) => in_array($get('status'), ['cancelled', 'declined']))
                                ->required(fn (Forms\Get $get) => in_array($get('status'), ['cancelled', 'declined']))
                                ->rows(2),

                            Textarea::make('admin_remark')
                                ->label('Admin Remark / Note (Optional)')
                                ->placeholder('Add internal staff note...')
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            $updateData = ['status' => $data['status']];
                            if (isset($data['tracking_id'])) {
                                $updateData['tracking_id'] = $data['tracking_id'];
                            }
                            if (isset($data['courier_partner'])) {
                                $updateData['courier_partner'] = $data['courier_partner'];
                            }
                            if (in_array($data['status'], ['cancelled', 'declined'])) {
                                $updateData['cancellation_reason'] = $data['cancellation_reason'] ?? null;
                                if (empty($record->cancelled_by)) {
                                    $updateData['cancelled_by'] = 'admin';
                                }
                            }
                            if (isset($data['admin_remark'])) {
                                $updateData['admin_remark'] = $data['admin_remark'];
                            }
                            $record->update($updateData);

                            \Filament\Notifications\Notification::make()
                                ->title('Order status updated successfully')
                                ->success()
                                ->send();
                        }),

                    // Quick action to view/add remark
                    Tables\Actions\Action::make('addRemark')
                        ->label('Remark')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('gray')
                        ->mountUsing(function (Forms\ComponentContainer $form, $record) {
                            $form->fill([
                                'admin_remark' => $record->admin_remark,
                            ]);
                        })
                        ->form([
                            Textarea::make('admin_remark')
                                ->label('Order Remark / Internal Comment')
                                ->placeholder('Enter internal notes or comments for this order...')
                                ->rows(4)
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'admin_remark' => $data['admin_remark'],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Order remark updated successfully')
                                ->success()
                                ->send();
                        }),
                    
                    // Custom action to update payment status (Admin only)
                    Tables\Actions\Action::make('updatePayment')
                        ->label('Update Payment')
                        ->icon('heroicon-o-credit-card')
                        ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                        ->form([
                            Select::make('payment_status')
                                ->options([
                                    "pending"=>"Pending",
                                    "paid"=>"Paid",
                                    "failed"=>"Failed",
                                    "refunded"=>"Refunded",
                                ])
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update(['payment_status' => $data['payment_status']]);
                        }),

                    // Verify & Recover Razorpay Payment action (Admin only)
                    Tables\Actions\Action::make('verifyRazorpay')
                        ->label('Verify / Recover Razorpay')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('warning')
                        ->visible(fn ($record) => (auth()->user()?->isAdmin() ?? false) 
                            && $record->payment_method === 'razorpay' 
                            && $record->payment_status !== 'paid' 
                            && !empty($record->razorpay_order_id))
                        ->requiresConfirmation()
                        ->modalHeading('Verify Razorpay Payment')
                        ->modalDescription(fn ($record) => "Query Razorpay API to check if payment for Order #{$record->order_number} ({$record->razorpay_order_id}) has been captured?")
                        ->action(function ($record) {
                            $razorpayService = new \App\Services\RazorpayService();
                            try {
                                $payments = $razorpayService->fetchOrderPayments($record->razorpay_order_id);
                                $paymentItems = $payments['items'] ?? [];
                                $capturedPayment = null;

                                foreach ($paymentItems as $payment) {
                                    if (($payment['status'] ?? '') === 'captured') {
                                        $capturedPayment = $payment;
                                        break;
                                    }
                                }

                                if ($capturedPayment) {
                                    $result = $razorpayService->markOrderAsPaid($record, $capturedPayment['id'], 'manual_recovery', $capturedPayment);
                                    \Filament\Notifications\Notification::make()
                                        ->title('Payment Verified & Recovered!')
                                        ->body("Order #{$record->order_number} marked as Paid. Payment ID: {$capturedPayment['id']}")
                                        ->success()
                                        ->send();
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('No Captured Payment Found')
                                        ->body("Razorpay reported no captured payment for Order #{$record->order_number}.")
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Razorpay Verification Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->isAdmin() ?? false),
                    Tables\Actions\BulkAction::make('updateStatusBulk')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('status')
                                ->options([
                                    "new"=>"New",
                                    "pending"=>"Pending",
                                    "processing"=>"Processing",
                                    "order_shipped"=>"Order Shipped",
                                    "completed"=>"Completed",
                                    "declined"=>"Declined",
                                    "cancelled"=>"Cancelled",
                                    "refunded"=>"Refunded",
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        }),

                    // Bulk Recover Razorpay Payments
                    Tables\Actions\BulkAction::make('recoverRazorpayPayments')
                        ->label('Recover Razorpay Payments')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('warning')
                        ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                        ->requiresConfirmation()
                        ->modalHeading('Recover Razorpay Payments')
                        ->modalDescription('Query Razorpay API for all selected orders. Any orders with captured payments will be marked as Paid and moved to Processing.')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $razorpayService = new \App\Services\RazorpayService();
                            $recovered = 0;
                            $skipped = 0;
                            $noPayment = 0;
                            $errors = 0;

                            foreach ($records as $record) {
                                if ($record->payment_method !== 'razorpay' || empty($record->razorpay_order_id)) {
                                    $skipped++;
                                    continue;
                                }

                                if ($record->payment_status === 'paid') {
                                    $skipped++;
                                    continue;
                                }

                                try {
                                    $payments = $razorpayService->fetchOrderPayments($record->razorpay_order_id);
                                    $paymentItems = $payments['items'] ?? [];
                                    $capturedPayment = null;

                                    foreach ($paymentItems as $payment) {
                                        if (($payment['status'] ?? '') === 'captured') {
                                            $capturedPayment = $payment;
                                            break;
                                        }
                                    }

                                    if ($capturedPayment) {
                                        $razorpayService->markOrderAsPaid($record, $capturedPayment['id'], 'manual_recovery', $capturedPayment);
                                        $recovered++;
                                    } else {
                                        $noPayment++;
                                    }
                                } catch (\Exception $e) {
                                    $errors++;
                                    \Illuminate\Support\Facades\Log::error("Manual recovery failed for Order #{$record->order_number}: " . $e->getMessage());
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Razorpay Recovery Complete')
                                ->body("{$recovered} recovered, {$noPayment} unpaid, {$skipped} skipped, {$errors} errors.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
