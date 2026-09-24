<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingSettingResource\Pages;
use App\Models\ShippingSetting;
use App\Services\ShippingCalculationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ShippingSettingResource extends Resource
{
    protected static ?string $model = ShippingSetting::class;

    protected static ?string $navigationGroup = 'Shipping';
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'Shipping & COD Rules';
    protected static ?string $modelLabel = 'Shipping & COD Rule';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'shipping-rules';

    /**
     * Protected by the existing 'shipping_methods' permission.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission('shipping_methods') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. Audit / Top Info
                Forms\Components\Section::make('Update Information')
                    ->schema([
                        Forms\Components\Placeholder::make('last_updated_at')
                            ->label('Last Updated')
                            ->content(function (?ShippingSetting $record) {
                                return $record?->updated_at
                                    ? $record->updated_at->format('d M Y, h:i A')
                                    : 'Not updated yet';
                            }),

                        Forms\Components\Placeholder::make('updated_by_name')
                            ->label('Updated By')
                            ->content(function (?ShippingSetting $record) {
                                return $record?->updatedBy?->name ?? 'System';
                            }),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(false),

                // 2. Main Tabs
                Forms\Components\Tabs::make('Shipping Configuration')
                    ->tabs([
                        // Tab 1: General & Free Shipping Threshold
                        Forms\Components\Tabs\Tab::make('General & Free Shipping')
                            ->schema([
                                Forms\Components\Section::make('Free Shipping Policy')
                                    ->description('Configure minimum cart value threshold required for customers to get free shipping.')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_free_shipping_enabled')
                                            ->label('Enable Free Shipping Offer')
                                            ->helperText('When enabled, orders whose subtotal reaches or exceeds the threshold will receive ₹0 base shipping.')
                                            ->default(true),

                                        Forms\Components\TextInput::make('min_order_for_free_shipping')
                                            ->label('Minimum Order Amount for Free Shipping (₹)')
                                            ->numeric()
                                            ->prefix('₹')
                                            ->required()
                                            ->default(999.00)
                                            ->helperText('e.g. ₹999. Orders with subtotal >= ₹999 get free base shipping.'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Base Rates & Packaging Buffer')
                                    ->description('Fallback rate and packaging weight added to parcels.')
                                    ->schema([
                                        Forms\Components\TextInput::make('default_flat_shipping')
                                            ->label('Default Flat Shipping Rate (₹)')
                                            ->numeric()
                                            ->prefix('₹')
                                            ->required()
                                            ->default(49.00)
                                            ->helperText('Used as standard base rate if weight-based shipping is turned off.'),

                                        Forms\Components\TextInput::make('packaging_buffer_weight')
                                            ->label('Packaging Buffer Weight (KG)')
                                            ->numeric()
                                            ->suffix('KG')
                                            ->step(0.01)
                                            ->required()
                                            ->default(0.100)
                                            ->helperText('Extra weight added for carton box, tape & bubble wrap (e.g. 0.100 KG / 100 grams) to avoid courier weight penalties.'),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 2: Weight-Based Shipping Slabs
                        Forms\Components\Tabs\Tab::make('Weight Slabs')
                            ->schema([
                                Forms\Components\Section::make('Weight-Based Rules')
                                    ->description('Calculate shipping charges dynamically based on parcel weight (KG).')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_weight_shipping_enabled')
                                            ->label('Enable Weight-Based Shipping')
                                            ->helperText('When enabled, shipping price will be picked from the weight slabs below.')
                                            ->default(true),

                                        Forms\Components\TextInput::make('extra_weight_per_kg_rate')
                                            ->label('Charge Per Extra 1 KG (Above Highest Slab)')
                                            ->numeric()
                                            ->prefix('₹')
                                            ->required()
                                            ->default(30.00)
                                            ->helperText('If order parcel weight exceeds the highest slab, charge this amount per extra 1 KG (e.g. ₹30/kg).'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Configured Weight Slabs')
                                    ->description('Define shipping cost brackets by weight range in Kilograms (KG).')
                                    ->schema([
                                        Forms\Components\Repeater::make('weight_slabs')
                                            ->label('Weight Brackets')
                                            ->addActionLabel('Add New Weight Slab')
                                            ->schema([
                                                Forms\Components\TextInput::make('label')
                                                    ->label('Slab Name')
                                                    ->placeholder('e.g. Up to 500g, 1kg to 2kg')
                                                    ->required(),

                                                Forms\Components\TextInput::make('min_weight')
                                                    ->label('Min Weight (KG)')
                                                    ->numeric()
                                                    ->step(0.001)
                                                    ->suffix('KG')
                                                    ->required(),

                                                Forms\Components\TextInput::make('max_weight')
                                                    ->label('Max Weight (KG)')
                                                    ->numeric()
                                                    ->step(0.001)
                                                    ->suffix('KG')
                                                    ->required(),

                                                Forms\Components\TextInput::make('price')
                                                    ->label('Shipping Cost (₹)')
                                                    ->numeric()
                                                    ->prefix('₹')
                                                    ->required(),
                                            ])
                                            ->columns(4)
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['label'] ?? null) . (isset($state['price']) ? " — ₹{$state['price']}" : '')),
                                    ]),
                            ]),

                        // Tab 3: COD Charges Slabs
                        Forms\Components\Tabs\Tab::make('COD Handling Charges')
                            ->schema([
                                Forms\Components\Section::make('Cash On Delivery (COD) Controls')
                                    ->description('Configure COD availability and tiered handling charges based on Cart Amount.')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_cod_enabled')
                                            ->label('Enable Cash On Delivery (COD)')
                                            ->helperText('Turn off to disable Cash on Delivery store-wide.')
                                            ->default(true),

                                        Forms\Components\TextInput::make('max_cod_order_amount')
                                            ->label('Maximum Order Amount for COD (₹)')
                                            ->numeric()
                                            ->prefix('₹')
                                            ->required()
                                            ->default(5000.00)
                                            ->helperText('Orders exceeding this value cannot be placed via COD to prevent courier RTO loss.'),

                                        Forms\Components\TextInput::make('default_cod_charge')
                                            ->label('Default / Fallback COD Fee (₹)')
                                            ->numeric()
                                            ->prefix('₹')
                                            ->required()
                                            ->default(49.00)
                                            ->helperText('Charged if cart value does not fall into any specific slab below.'),
                                    ])
                                    ->columns(3),

                                Forms\Components\Section::make('Tiered COD Charges Slabs ("Code Shipping")')
                                    ->description('Set COD fee based on Cart Subtotal range (e.g. Below ₹500 → ₹29, ₹500-₹799 → ₹49, ₹800+ → ₹79).')
                                    ->schema([
                                        Forms\Components\Repeater::make('cod_slabs')
                                            ->label('COD Amount Slabs')
                                            ->addActionLabel('Add New COD Slab')
                                            ->schema([
                                                Forms\Components\TextInput::make('label')
                                                    ->label('Slab Description')
                                                    ->placeholder('e.g. Below ₹500, ₹500 to ₹799')
                                                    ->required(),

                                                Forms\Components\TextInput::make('min_amount')
                                                    ->label('Min Cart Amount (₹)')
                                                    ->numeric()
                                                    ->prefix('₹')
                                                    ->required(),

                                                Forms\Components\TextInput::make('max_amount')
                                                    ->label('Max Cart Amount (₹)')
                                                    ->numeric()
                                                    ->prefix('₹')
                                                    ->required(),

                                                Forms\Components\TextInput::make('cod_charge')
                                                    ->label('COD Fee (₹)')
                                                    ->numeric()
                                                    ->prefix('₹')
                                                    ->required(),
                                            ])
                                            ->columns(4)
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['label'] ?? null) . (isset($state['cod_charge']) ? " — Fee: ₹{$state['cod_charge']}" : '')),
                                    ]),
                            ]),

                        // Tab 4: Interactive Live Shipping & COD Simulator
                        Forms\Components\Tabs\Tab::make('Live Test Simulator')
                            ->schema([
                                Forms\Components\Section::make('Shipping & COD Rate Calculator')
                                    ->description('Test your configured rules in real time. Adjust the values below to simulate customer checkout calculation.')
                                    ->schema([
                                        Forms\Components\TextInput::make('sim_subtotal')
                                            ->label('1. Test Cart Subtotal (₹)')
                                            ->numeric()
                                            ->prefix('₹')
                                            ->default(650)
                                            ->live()
                                            ->helperText('Try ₹450 (< 500), ₹650 (500-799), ₹850 (>= 800), or ₹1200 (>= 999).'),

                                        Forms\Components\TextInput::make('sim_weight')
                                            ->label('2. Test Parcel Weight (KG)')
                                            ->numeric()
                                            ->suffix('KG')
                                            ->step(0.1)
                                            ->default(0.8)
                                            ->live()
                                            ->helperText('Weight of books in cart (e.g. 0.4kg, 0.8kg, 1.5kg).'),

                                        Forms\Components\Select::make('sim_method')
                                            ->label('3. Shipping Method')
                                            ->options(function () {
                                                return \App\Models\ShippingMethod::query()
                                                    ->where('is_active', true)
                                                    ->orderBy('sort_order')
                                                    ->pluck('name', 'code')
                                                    ->toArray();
                                            })
                                            ->default('standard')
                                            ->live()
                                            ->native(false)
                                            ->helperText('Test Standard Shipping (Weight Slabs) vs Express Delivery (Flat Rate).'),

                                        Forms\Components\Select::make('sim_payment')
                                            ->label('4. Payment Method')
                                            ->options([
                                                'cod' => 'Cash On Delivery (COD)',
                                                'prepaid' => 'Prepaid / UPI / Online (Razorpay)',
                                            ])
                                            ->default('cod')
                                            ->live()
                                            ->native(false)
                                            ->helperText('Select payment type to see COD charges applied.'),

                                        Forms\Components\Placeholder::make('sim_calculation_result')
                                            ->label('Calculation Result (Real-Time)')
                                            ->columnSpanFull()
                                            ->content(function (Forms\Get $get, ?ShippingSetting $record) {
                                                $subtotal = (float) ($get('sim_subtotal') ?? 650);
                                                $weight = (float) ($get('sim_weight') ?? 0.8);
                                                $payment = (string) ($get('sim_payment') ?? 'cod');
                                                $method = (string) ($get('sim_method') ?? 'standard');

                                                // Create in-memory model reflecting current unsaved/saved form state
                                                $tempSetting = new ShippingSetting([
                                                    'is_free_shipping_enabled' => (bool) $get('is_free_shipping_enabled'),
                                                    'min_order_for_free_shipping' => (float) $get('min_order_for_free_shipping'),
                                                    'default_flat_shipping' => (float) $get('default_flat_shipping'),
                                                    'packaging_buffer_weight' => (float) $get('packaging_buffer_weight'),
                                                    'is_weight_shipping_enabled' => (bool) $get('is_weight_shipping_enabled'),
                                                    'extra_weight_per_kg_rate' => (float) $get('extra_weight_per_kg_rate'),
                                                    'weight_slabs' => $get('weight_slabs'),
                                                    'is_cod_enabled' => (bool) $get('is_cod_enabled'),
                                                    'max_cod_order_amount' => (float) $get('max_cod_order_amount'),
                                                    'default_cod_charge' => (float) $get('default_cod_charge'),
                                                    'cod_slabs' => $get('cod_slabs'),
                                                ]);

                                                $svc = new ShippingCalculationService();
                                                $res = $svc->calculateForMethod($method, $subtotal, $weight, $payment, $tempSetting);

                                                $isFree = $res['is_free_shipping'];
                                                $freeBadge = $isFree
                                                    ? '<span style="background-color:#dcfce7; color:#15803d; padding:2px 8px; border-radius:9999px; font-weight:600; font-size:12px;">Free Shipping Applied!</span>'
                                                    : '<span style="background-color:#fef3c7; color:#b45309; padding:2px 8px; border-radius:9999px; font-weight:600; font-size:12px;">Add ₹' . number_format($res['amount_needed_for_free_shipping'], 2) . ' more for Free Delivery</span>';

                                                $codBadge = $res['is_cod']
                                                    ? '<span style="background-color:#fee2e2; color:#b91c1c; padding:2px 8px; border-radius:9999px; font-weight:600; font-size:12px;">COD Fee: ₹' . number_format($res['cod_amount'], 2) . '</span>'
                                                    : '<span style="background-color:#e0e7ff; color:#4338ca; padding:2px 8px; border-radius:9999px; font-weight:600; font-size:12px;">Prepaid (No COD Fee)</span>';

                                                return new HtmlString("
                                                    <div style='background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:18px; margin-top:8px;'>
                                                        <div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;'>
                                                            <div style='font-weight:700; font-size:15px; color:#1e293b;'>Order Simulation Breakdown</div>
                                                            <div style='display:flex; gap:8px;'>
                                                                {$freeBadge}
                                                                {$codBadge}
                                                            </div>
                                                        </div>

                                                        <div style='display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:12px;'>
                                                            <div style='background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:10px;'>
                                                                <div style='font-size:12px; color:#64748b; font-weight:500;'>Effective Weight</div>
                                                                <div style='font-size:16px; font-weight:700; color:#0f172a; margin-top:2px;'>{$res['effective_weight_kg']} KG</div>
                                                                <div style='font-size:11px; color:#94a3b8;'>({$res['item_weight_kg']}kg + {$res['packaging_buffer_kg']}kg buffer)</div>
                                                            </div>

                                                            <div style='background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:10px;'>
                                                                <div style='font-size:12px; color:#64748b; font-weight:500;'>Base Shipping</div>
                                                                <div style='font-size:16px; font-weight:700; color:" . ($isFree ? '#16a34a' : '#0f172a') . "; margin-top:2px;'>₹" . number_format($res['base_shipping_amount'], 2) . "</div>
                                                                <div style='font-size:11px; color:#64748b;'>" . ($isFree ? 'Threshold Reached' : $res['weight_explanation']) . "</div>
                                                            </div>

                                                            <div style='background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:10px;'>
                                                                <div style='font-size:12px; color:#64748b; font-weight:500;'>COD Charge</div>
                                                                <div style='font-size:16px; font-weight:700; color:#b91c1c; margin-top:2px;'>₹" . number_format($res['cod_amount'], 2) . "</div>
                                                                <div style='font-size:11px; color:#64748b;'>{$res['cod_explanation']}</div>
                                                            </div>

                                                            <div style='background:#f1f5f9; border:1px solid #cbd5e1; border-radius:8px; padding:10px;'>
                                                                <div style='font-size:12px; color:#475569; font-weight:600;'>Total Customer Pays</div>
                                                                <div style='font-size:18px; font-weight:800; color:#0f172a; margin-top:2px;'>₹" . number_format($res['final_order_total'], 2) . "</div>
                                                                <div style='font-size:11px; color:#64748b;'>Cart: ₹" . number_format($res['cart_subtotal'], 2) . " + Delivery: ₹" . number_format($res['total_delivery_cost'], 2) . "</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ");
                                            }),
                                    ])
                                    ->columns(4),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('min_order_for_free_shipping')
                    ->label('Free Shipping Min Order')
                    ->money('INR'),
                Tables\Columns\IconColumn::make('is_free_shipping_enabled')
                    ->label('Free Shipping')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_weight_shipping_enabled')
                    ->label('Weight Slabs Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_cod_enabled')
                    ->label('COD Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y, h:i A')
                    ->label('Last Updated'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingSettings::route('/'),
            'edit' => Pages\EditShippingSetting::route('/{record}/edit'),
        ];
    }

    public static function resolveRecordRouteBinding(int | string $key): ?\Illuminate\Database\Eloquent\Model
    {
        return ShippingSetting::firstOrCreate(
            ['id' => $key],
            [
                'is_free_shipping_enabled' => true,
                'min_order_for_free_shipping' => 999.00,
                'default_flat_shipping' => 49.00,
                'is_weight_shipping_enabled' => true,
                'packaging_buffer_weight' => 0.100,
                'extra_weight_per_kg_rate' => 30.00,
                'is_cod_enabled' => true,
                'max_cod_order_amount' => 5000.00,
                'default_cod_charge' => 49.00,
            ]
        );
    }

    public static function getNavigationUrl(): string
    {
        $record = ShippingSetting::current();

        return static::getUrl('edit', ['record' => $record->id]);
    }
}
