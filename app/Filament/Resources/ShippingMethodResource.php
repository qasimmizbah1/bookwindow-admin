<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingMethodResource\Pages;
use App\Models\ShippingMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Shipping';
    protected static ?string $navigationLabel = 'Shipping Methods';
    protected static ?string $modelLabel = 'Shipping Method';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission('shipping_methods') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shipping Method Details')
                    ->description('Set up method title, delivery timeframe and unique system code.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Method Name')
                            ->placeholder('e.g. Standard Shipping, Express Delivery, Jaipur Same-Day')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Forms\Set $set, Forms\Get $get) {
                                if ($operation === 'create' && empty($get('code')) && !empty($state)) {
                                    $set('code', Str::slug($state, '_'));
                                }
                            }),

                        Forms\Components\TextInput::make('code')
                            ->label('System Code (Identifier)')
                            ->placeholder('e.g. standard, express, jaipur_sameday')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Unique lowercase identifier used by system/API.')
                            ->alphaDash(),

                        Forms\Components\TextInput::make('delivery_time')
                            ->label('Estimated Delivery Time')
                            ->placeholder('e.g. 4 - 7 Business Days, 24 - 48 Hours')
                            ->maxLength(255)
                            ->helperText('Displayed to customer during checkout delivery selection.'),

                        Forms\Components\Textarea::make('description')
                            ->label('Customer Description')
                            ->placeholder('Brief note explaining carrier/dispatch service (optional)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Pricing & Calculation Rules')
                    ->description('Define how the shipping price is calculated for this method.')
                    ->schema([
                        Forms\Components\Select::make('calculation_type')
                            ->label('Calculation Type')
                            ->options([
                                'weight_based' => 'Dynamic Weight Slabs (Uses Weight Slabs from Settings)',
                                'flat_rate' => 'Fixed Flat Rate (Charges the fixed amount below)',
                                'free' => 'Always Free Delivery (₹0)',
                            ])
                            ->default('weight_based')
                            ->required()
                            ->live()
                            ->native(false)
                            ->helperText(fn (Forms\Get $get) => match ($get('calculation_type')) {
                                'weight_based' => 'Calculates delivery fee based on total cart book weight using the slabs configured in Shipping & COD Rules.',
                                'flat_rate' => 'Always charges the fixed price entered below, regardless of cart weight.',
                                'free' => 'Always free (₹0) delivery fee.',
                                default => ''
                            }),

                        Forms\Components\TextInput::make('price')
                            ->label('Price / Base Fee (₹)')
                            ->numeric()
                            ->prefix('₹')
                            ->required()
                            ->default(49.00)
                            ->helperText('For Flat Rate: exact charge. For Weight-Based: fallback price.'),

                        Forms\Components\Toggle::make('is_free_shipping_eligible')
                            ->label('Eligible for Free Shipping Threshold')
                            ->default(true)
                            ->helperText('If ON, orders meeting the Free Shipping threshold (e.g. ₹999) get ₹0 fee. Turn OFF for Express/Priority so it is always charged.')
                            ->visible(fn (Forms\Get $get) => $get('calculation_type') !== 'free'),

                        Forms\Components\Toggle::make('is_cod_allowed')
                            ->label('Allow Cash On Delivery (COD)')
                            ->default(true)
                            ->helperText('Allow customers to select COD payment with this delivery method.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Is Active (Enabled)')
                            ->default(true)
                            ->helperText('Only active shipping methods are shown to customers.'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower number displays first at checkout.'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Method')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn (ShippingMethod $record): ?string => $record->delivery_time),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('calculation_type')
                    ->label('Rate Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'weight_based' => 'Weight Slabs',
                        'flat_rate' => 'Flat Rate',
                        'free' => 'Always Free',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'weight_based' => 'primary',
                        'flat_rate' => 'warning',
                        'free' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('price')
                    ->label('Base Price')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_free_shipping_eligible')
                    ->label('Free Threshold')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_cod_allowed')
                    ->label('COD Allowed')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit' => Pages\EditShippingMethod::route('/{record}/edit'),
        ];
    }
}