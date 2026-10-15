<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UpsellSettingResource\Pages;
use App\Models\UpsellSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UpsellSettingResource extends Resource
{
    protected static ?string $model = UpsellSetting::class;

    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Checkout Upsell';
    protected static ?string $modelLabel = 'Checkout Upsell Setting';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'checkout-upsell';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() || (auth()->user()?->hasPermission('global_settings') ?? false);
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
                Forms\Components\Section::make('Update Information')
                    ->schema([
                        Forms\Components\Placeholder::make('last_updated_at')
                            ->label('Last Updated')
                            ->content(function (?UpsellSetting $record) {
                                return $record?->updated_at
                                    ? $record->updated_at->format('d M Y, h:i A')
                                    : 'Not updated yet';
                            }),

                        Forms\Components\Placeholder::make('updated_by_name')
                            ->label('Updated By')
                            ->content(function (?UpsellSetting $record) {
                                return $record?->updatedBy?->name ?? 'System';
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Tabs::make('Upsell & Recommendation Configuration')
                    ->tabs([
                        // TAB 1: VISIBILITY & STATUS
                        Forms\Components\Tabs\Tab::make('General Visibility')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Forms\Components\Section::make('Feature Controls')
                                    ->description('Enable or disable checkout recommendations across the storefront.')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_enabled')
                                            ->label('Master Switch: Enable Upsell & Recommendations')
                                            ->helperText('When turned off, both the popup modal and the inline recommendations on checkout will be hidden.')
                                            ->default(true),

                                        Forms\Components\Toggle::make('is_popup_enabled')
                                            ->label('Enable Checkout Modal Popup')
                                            ->helperText('Shows the suggested books popup modal when a customer lands on the checkout page.')
                                            ->default(true),

                                        Forms\Components\Toggle::make('is_inline_enabled')
                                            ->label('Enable Checkout Inline Section')
                                            ->helperText('Shows the recommended books section on checkout Step 1 under the coupon box.')
                                            ->default(true),
                                    ])
                                    ->columns(3),
                            ]),

                        // TAB 2: POPUP MODAL
                        Forms\Components\Tabs\Tab::make('Popup Modal')
                            ->icon('heroicon-o-window')
                            ->schema([
                                Forms\Components\Section::make('Modal Header Content')
                                    ->description('Customize the text shown at the top of the suggested books modal popup.')
                                    ->schema([
                                        Forms\Components\TextInput::make('modal_title')
                                            ->label('Popup Modal Title')
                                            ->placeholder('Suggested Books You May Also Like')
                                            ->required()
                                            ->maxLength(120),

                                        Forms\Components\TextInput::make('modal_subtitle')
                                            ->label('Popup Modal Subtitle')
                                            ->placeholder('Add these recommended books to your order before checkout')
                                            ->maxLength(180),
                                    ])
                                    ->columns(2),
                            ]),

                        // TAB 3: INLINE SECTION & CARDS
                        Forms\Components\Tabs\Tab::make('Section & Cards')
                            ->icon('heroicon-o-queue-list')
                            ->schema([
                                Forms\Components\Section::make('Section Headings & Buttons')
                                    ->description('Configure section labels and books display limits.')
                                    ->schema([
                                        Forms\Components\TextInput::make('section_title')
                                            ->label('Section Title')
                                            ->placeholder('Make it perfect')
                                            ->required()
                                            ->maxLength(80),

                                        Forms\Components\TextInput::make('section_subtitle')
                                            ->label('Section Subtitle')
                                            ->placeholder('Customers who bought items in your cart also added these books')
                                            ->maxLength(180),

                                        Forms\Components\TextInput::make('add_button_text')
                                            ->label('Add Button Text')
                                            ->placeholder('Add')
                                            ->required()
                                            ->maxLength(30),

                                        Forms\Components\TextInput::make('max_products')
                                            ->label('Max Books to Fetch')
                                            ->numeric()
                                            ->minValue(3)
                                            ->maxValue(20)
                                            ->default(8)
                                            ->helperText('Number of related books to randomly fetch and display in recommendations.'),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Master Status')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_popup_enabled')
                    ->label('Popup Modal')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_inline_enabled')
                    ->label('Inline Section')
                    ->boolean(),
                Tables\Columns\TextColumn::make('max_products')
                    ->label('Max Books'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, h:i A'),
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
            'index' => Pages\ListUpsellSettings::route('/'),
            'edit' => Pages\EditUpsellSetting::route('/{record}/edit'),
        ];
    }

    public static function resolveRecordRouteBinding(int | string $key): ?\Illuminate\Database\Eloquent\Model
    {
        return UpsellSetting::firstOrCreate(
            ['id' => $key],
            [
                'is_enabled' => true,
                'is_popup_enabled' => true,
                'is_inline_enabled' => true,
                'modal_title' => 'Suggested Books You May Also Like',
                'modal_subtitle' => 'Add these recommended books to your order before checkout',
                'section_title' => 'Make it perfect',
                'section_subtitle' => 'Customers who bought items in your cart also added these books',
                'add_button_text' => 'Add',
                'max_products' => 8,
            ]
        );
    }

    public static function getNavigationUrl(): string
    {
        $record = UpsellSetting::current();

        return static::getUrl('edit', ['record' => $record->id]);
    }
}
