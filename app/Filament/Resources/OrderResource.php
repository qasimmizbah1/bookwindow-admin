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
        return static::getModel()::where('status', '=', 'pending')->count();
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
                                                        return $record->customer_phone ?? 'N/A';
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


                                                    TextInput::make('tracking_id')
                                                    ->label('Tracking ID')
                                                    ->live()
                                                    ->visible(fn (Forms\Get $get) => $get('status') === 'order_shipped')
                                                    ->required(fn (Forms\Get $get) => $get('status') === 'order_shipped')
                                                     ->columnSpanFull(),

                                
                                                
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
                                                    ->label('Delivery Amount')
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
                                                    ->label('Order Notes')
                                                    ->content(function ($record) {
                                                        return $record->notes ?? 'No notes';
                                                    })
                                                    ->columnSpanFull(),
                                            ]),
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
                                                
                                                $html = '<div class="bg-white rounded-lg overflow-hidden shadow-sm border border-gray-200">
                                                    <table class="w-full border-collapse">
                                                        <thead>
                                                            <tr class="bg-gray-50 border-b border-gray-200">
                                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200">';
                                                
                                                $counter = 1;
                                                foreach ($record->items as $item) {
                                                    $productName = $item->product ? $item->product->name : 'Product #' . $item->product_id;
                                                    $subtotal = $item->quantity * $item->price;
                                                    $html .= '<tr class="transition-colors duration-150">
                                                        <td class="px-4 py-3 text-sm text-gray-500">' . $counter . '</td>
                                                        <td class="px-4 py-3 text-sm font-medium text-gray-700">' . e($productName) . '</td>
                                                        <td class="px-4 py-3 text-sm text-center text-gray-700">' . $item->quantity . '</td>
                                                        <td class="px-4 py-3 text-sm text-right text-gray-700">' . format_currency($item->price, 2) . '</td>
                                                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-700">' . format_currency($subtotal, 2) . '</td>
                                                    </tr>';
                                                    $counter++;
                                                }
                                                
                                                $html .= '</tbody></table></div>';
                                                
                                                // Add summary with better styling
                                                $totalItems = $record->items->sum('quantity');
                                                $totalAmount = $record->items->sum(function ($item) {
                                                    return $item->quantity * $item->price;
                                                });
                                                
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
                                                        </div>
                                                        <div class="flex justify-between py-2 border-b border-gray-200">
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
                    ]),
                
                // Tables\Columns\TextColumn::make('payment_method')
                //     ->label('Payment')
                //     ->badge()
                //     ->colors([
                //         'success' => 'cod',
                //         'primary' => 'card',
                //         'warning' => 'razorpay',
                //     ]),
                
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
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money()
                    ->sortable()
                    ->searchable()
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
                // Only import action, no create button
                Action::make('import')
                    ->label('Import Orders')
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
                        ->label('Edit'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Delete'),
                    // Custom action to update status quickly
                    Tables\Actions\Action::make('updateStatus')
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
                        ->action(function ($record, array $data) {
                            $record->update(['status' => $data['status']]);
                        }),
                    
                    // Custom action to update payment status
                    Tables\Actions\Action::make('updatePayment')
                        ->label('Update Payment')
                        ->icon('heroicon-o-credit-card')
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
                    Tables\Actions\DeleteBulkAction::make(),
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
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}