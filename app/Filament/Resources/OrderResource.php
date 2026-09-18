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

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = "Shop";

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
                })->whereIn('status', ['new', 'pending', 'processing'])->count();
            }
            return '0';
        }

        return (string) static::getModel()::where('status', '=', 'pending')->count();
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

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Use Tabs for organization
                Tabs::make('Order Details')
                    ->tabs([
                        Tab::make('Customer & Order Info')
                            ->schema([
                                // Customer Information Section - Show as view only
                                Section::make('Customer Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('full_name')
                                                    ->label('Full Name')
                                                    ->content(function ($record) {
                                                        return ($record->first_name ?? '') . ' ' . ($record->last_name ?? '');
                                                    }),
                                                
                                                Placeholder::make('email')
                                                    ->label('Email')
                                                    ->content(function ($record) {
                                                        return $record->email ?? 'N/A';
                                                    }),
                                                
                                                Placeholder::make('customer_phone')
                                                    ->label('Phone Number')
                                                    ->content(function ($record) {
                                                        return $record?->formatted_phone ?? 'N/A';
                                                    }),
                                            ]),
                                    ]),

                                // Shipping Address Section - Show as view only
                                Section::make('Shipping Address')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('address')
                                                    ->label('Address Line 1')
                                                    ->content(function ($record) {
                                                        return $record->address ?? 'N/A';
                                                    })
                                                    ->columnSpanFull(),
                                                
                                                Placeholder::make('address_2')
                                                    ->label('Address Line 2')
                                                    ->content(function ($record) {
                                                        return $record->address_2 ?? 'N/A';
                                                    })
                                                    ->columnSpanFull(),
                                                
                                                Placeholder::make('city')
                                                    ->label('City')
                                                    ->content(function ($record) {
                                                        return $record->city ?? 'N/A';
                                                    }),
                                                
                                                Placeholder::make('state')
                                                    ->label('State/Province')
                                                    ->content(function ($record) {
                                                        return $record->state ?? 'N/A';
                                                    }),
                                                
                                                Placeholder::make('zip_code')
                                                    ->label('Postal Code')
                                                    ->content(function ($record) {
                                                        return $record->zip_code ?? 'N/A';
                                                    }),
                                                
                                                Placeholder::make('country')
                                                    ->label('Country')
                                                    ->content(function ($record) {
                                                        return $record->country ?? 'N/A';
                                                    }),
                                            ]),
                                    ]),

                                // Order Information Section
                                Section::make('Order Information')
                                    ->schema([
                                        Grid::make(2)
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

                                                TextInput::make('tracking_id')
                                                    ->label('Tracking ID')
                                                    ->placeholder('e.g. DTDC12345678, EK123456789IN')
                                                    ->live()
                                                    ->visible(fn (Forms\Get $get) => in_array($get('status'), ['order_shipped', 'completed']))
                                                    ->required(fn (Forms\Get $get) => $get('status') === 'order_shipped'),

                                                TextInput::make('courier_partner')
                                                    ->label('Courier Partner Name')
                                                    ->placeholder('Enter Courier Partner Name (e.g. DTDC, India Post)')
                                                    ->live()
                                                    ->visible(fn (Forms\Get $get) => in_array($get('status'), ['order_shipped', 'completed'])),

                                
                                                
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
                                    ]),

                                // Order Financial Details - Show as view only
                                Section::make('Financial Details')
                                    ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Placeholder::make('subtotal')
                                                    ->label('Subtotal')
                                                    ->content(function ($record) {
                                                        return format_currency($record->subtotal ?? 0, 2);
                                                    }),
                                                
                                                Placeholder::make('discount_amount')
                                                    ->label('Discount')
                                                    ->content(function ($record) {
                                                        return format_currency($record->discount_amount ?? 0, 2);
                                                    }),
                                                
                                                Placeholder::make('tax_amount')
                                                    ->label('Tax')
                                                    ->content(function ($record) {
                                                        return format_currency($record->tax_amount ?? 0, 2);
                                                    }),
                                                
                                                Placeholder::make('shipping_amount')
                                                    ->label('Shipping Cost')
                                                    ->content(function ($record) {
                                                        return format_currency($record->shipping_amount ?? 0, 2);
                                                    }),
                                                
                                                Placeholder::make('delivery_amount')
                                                    ->label('COD')
                                                    ->visible(fn ($record) => $record && (strtolower($record->payment_method ?? '') === 'cod' || ($record->delivery_amount ?? 0) > 0))
                                                    ->content(function ($record) {
                                                        return format_currency($record->delivery_amount ?? 0, 2);
                                                    }),
                                                
                                                Placeholder::make('total_amount')
                                                    ->label('Total Amount')
                                                    ->content(function ($record) {
                                                        return format_currency($record->total_amount ?? 0, 2);
                                                    }),
                                            ]),
                                    ]),

                                // Additional Information - Show as view only
                                Section::make('Additional Information')
                                    ->schema([
                                        Grid::make(2)
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
                                    ]),

                                // Admin Remarks & Internal Comments Section
                                Section::make('Admin Remarks & Internal Comments')
                                    ->description('Internal staff comments and order remarks (not visible to customer).')
                                    ->schema([
                                        Textarea::make('admin_remark')
                                            ->label('Order Remark / Comment')
                                            ->placeholder('Add internal staff notes or comments about this order...')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        
                        Tab::make('Products / Items')
                            ->schema([
                                Section::make('Order Items')
                                    ->schema([
                                        // Display items as a formatted table with proper styling
                                        Placeholder::make('items')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record || !$record->items || $record->items->count() == 0) {
                                                    return '<div class="text-center text-gray-500 py-8">No items found in this order</div>';
                                                }
                                                
                                                $isVendor = auth()->user()?->isVendor() ?? false;
                                                $vendor = auth()->user()?->vendor;

                                                $items = ($isVendor && $vendor)
                                                    ? $record->items->where('vendor_id', $vendor->id)
                                                    : $record->items;

                                                if ($items->isEmpty()) {
                                                    return '<div class="text-center text-gray-500 py-8">No products belonging to your store found in this order.</div>';
                                                }

                                                $html = '<div class="bg-white rounded-lg overflow-hidden shadow-sm border border-gray-200">
                                                    <table class="w-full border-collapse">
                                                        <thead>
                                                            <tr class="bg-gray-50 border-b border-gray-200">
                                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                                                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200">';
                                                
                                                $counter = 1;
                                                foreach ($items as $item) {
                                                    $productName = $item->product ? $item->product->name : ($item->product_name ?? 'Product #' . $item->product_id);
                                                    $subtotal = ($item->quantity ?? 1) * ($item->price ?? 0);
                                                    $html .= '<tr class="transition-colors duration-150">
                                                        <td class="px-4 py-3 text-sm text-gray-500">' . $counter . '</td>
                                                        <td class="px-4 py-3 text-sm font-medium text-gray-700">' . e($productName) . '</td>
                                                        <td class="px-4 py-3 text-sm font-medium text-gray-700">' . e($item->product->model ?? '') . '</td>
                                                        <td class="px-4 py-3 text-sm text-center text-gray-700">' . ($item->quantity ?? 1) . '</td>
                                                        <td class="px-4 py-3 text-sm text-right text-gray-700">' . format_currency($item->price ?? 0, 2) . '</td>
                                                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-700">' . format_currency($subtotal, 2) . '</td>
                                                    </tr>';
                                                    $counter++;
                                                }
                                                
                                                $html .= '</tbody></table></div>';
                                                
                                                $totalItems = $items->sum('quantity');
                                                $totalAmount = $items->sum(function ($item) {
                                                    return ($item->quantity ?? 1) * ($item->price ?? 0);
                                                });
                                                
                                                if ($isVendor) {
                                                    $commissionRate = $vendor->commission_rate;
                                                    $gstRate = $vendor->commission_gst_rate;
                                                    $commissionAmount = $vendor->calculateCommissionFee($totalAmount);
                                                    $gstAmount = $vendor->calculateCommissionGst($totalAmount);
                                                    $totalDeduction = $vendor->calculateTotalDeduction($totalAmount);
                                                    $netEarnings = $vendor->calculateVendorPayout($totalAmount);

                                                    $html .= '<div class="mt-6 bg-gray-50 rounded-lg p-6 border border-gray-200">
                                                        <div class="max-w-md ml-auto">
                                                            <div class="flex justify-between py-2 border-b border-gray-200">
                                                                <span class="text-sm font-medium text-gray-600">Your Items Total:</span>
                                                                <span class="text-sm font-semibold text-gray-700">' . $totalItems . ' item(s)</span>
                                                            </div>
                                                            <div class="flex justify-between py-2 border-b border-gray-200">
                                                                <span class="text-sm font-medium text-gray-600">Gross Sales Amount:</span>
                                                                <span class="text-sm font-semibold text-gray-700">' . format_currency($totalAmount, 2) . '</span>
                                                            </div>
                                                            <div class="flex justify-between py-2 border-b border-gray-200 text-amber-700">
                                                                <span class="text-sm font-medium">Platform Fee (' . $commissionRate . '%):</span>
                                                                <span class="text-sm font-semibold">-' . format_currency($commissionAmount, 2) . '</span>
                                                            </div>
                                                            <div class="flex justify-between py-2 border-b border-gray-200 text-amber-700">
                                                                <span class="text-sm font-medium">GST on Fee (' . $gstRate . '%):</span>
                                                                <span class="text-sm font-semibold">-' . format_currency($gstAmount, 2) . '</span>
                                                            </div>
                                                            <div class="flex justify-between py-2 border-b border-gray-200 text-red-700 font-medium">
                                                                <span class="text-sm font-semibold">Total Platform Charges:</span>
                                                                <span class="text-sm font-bold">-' . format_currency($totalDeduction, 2) . '</span>
                                                            </div>
                                                            <div class="flex justify-between pt-3 mt-1">
                                                                <span class="text-base font-bold text-gray-800">Your Net Payout:</span>
                                                                <span class="text-xl font-bold text-emerald-600">' . format_currency($netEarnings, 2) . '</span>
                                                            </div>
                                                        </div>
                                                    </div>';
                                                } else {
                                                    $html .= '<div class="mt-6 bg-gray-50 rounded-lg p-6 border border-gray-200">
                                                        <div class="max-w-md ml-auto">
                                                            <div class="flex justify-between py-2 border-b border-gray-200">
                                                                <span class="text-sm font-medium text-gray-600">Total Items:</span>
                                                                <span class="text-sm font-semibold text-gray-700">' . $totalItems . '</span>
                                                            </div>
                                                            <div class="flex justify-between py-2 border-b border-gray-200">
                                                                <span class="text-sm font-medium text-gray-600">Items Subtotal:</span>
                                                                <span class="text-sm font-semibold text-gray-700">' . format_currency($totalAmount, 2) . '</span>
                                                            </div>
                                                            <div class="flex justify-between py-2 border-b border-gray-200">
                                                                <span class="text-sm font-medium text-gray-600">Shipping:</span>
                                                                <span class="text-sm font-semibold text-gray-700">' . format_currency($record->shipping_amount ?? 0, 2) . '</span>
                                                            </div>';

                                                    if ($record && (strtolower($record->payment_method ?? '') === 'cod' || ($record->delivery_amount ?? 0) > 0)) {
                                                        $html .= '<div class="flex justify-between py-2 border-b border-gray-200">
                                                            <span class="text-sm font-medium text-gray-600">COD:</span>
                                                            <span class="text-sm font-semibold text-gray-700">' . format_currency($record->delivery_amount ?? 0, 2) . '</span>
                                                        </div>';
                                                    }

                                                    $html .= '<div class="flex justify-between py-2 border-b border-gray-200">
                                                                <span class="text-sm font-medium text-gray-600">Discount:</span>
                                                                <span class="text-sm font-semibold text-gray-700">-' . format_currency($record->discount_amount ?? 0, 2) . '</span>
                                                            </div>
                                                            <div class="flex justify-between py-2 border-b border-gray-200">
                                                                <span class="text-sm font-medium text-gray-600">Tax:</span>
                                                                <span class="text-sm font-semibold text-gray-700">' . format_currency($record->tax_amount ?? 0, 2) . '</span>
                                                            </div>
                                                            <div class="flex justify-between pt-3 mt-1">
                                                                <span class="text-base font-bold text-gray-700">Grand Total:</span>
                                                                <span class="text-xl font-bold text-gray-700">' . format_currency($record->total_amount ?? 0, 2) . '</span>
                                                            </div>
                                                        </div>
                                                    </div>';
                                                }
                                                
                                                return new \Illuminate\Support\HtmlString($html);
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),
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
                        'success' => 'completed',
                        'danger' => 'declined',
                        'danger' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'new',
                        'heroicon-o-exclamation-circle' => 'pending',
                        'heroicon-o-arrow-path' => 'processing',
                        'heroicon-o-check-circle' => 'completed',
                        'heroicon-o-x-circle' => 'declined',
                        'heroicon-o-x-mark' => 'cancelled',
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        "new"=>"New",
                        "pending"=>"Pending",
                        "processing"=>"Processing",
                        "order_shipped"=>"Order Shipped",
                        "completed"=>"Completed",
                        "declined"=>"Declined",
                        "cancelled"=>"Cancelled",
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
                                    "completed"=>"Completed",
                                    "declined"=>"Declined",
                                    "cancelled"=>"Cancelled",
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        }),
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