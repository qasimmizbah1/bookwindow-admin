<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Filament\Resources\CouponResource\RelationManagers;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Illuminate\Support\Str;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;


class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';


    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = "Shop";

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Update Information')
                ->schema([
                    Forms\Components\Placeholder::make('last_updated_at')
                        ->label('Last Updated')
                        ->content(function (?Coupon $record) {
                            return $record?->updated_at
                                ? $record->updated_at->format('d M Y, h:i A')
                                : 'Not updated yet';
                        }),

                    Forms\Components\Placeholder::make('updated_by')
                        ->label('Updated By')
                        ->content(function (?Coupon $record) {
                            return $record?->updatedBy?->name ?? 'Not available';
                        }),
                ])
                ->columns(2)
                ->visible(fn (string $operation) => $operation === 'edit'),

                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('type')
                    ->options([
                        'fixed' => 'Fixed Amount',
                        'percent' => 'Percentage',
                    ])
                    ->required(),

                   //Category
                            Forms\Components\Select::make(name: 'category_id')
                             ->relationship(
                                 name:'category', 
                                 titleAttribute:'name',
                             )
                            ->multiple() 
                            ->preload()
                            ->searchable()
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state) {
                            if (is_string($state)) {
                            $component->state(json_decode($state, true));
                            } })
                             ,

                Forms\Components\TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->rules(['min:0']),
                Forms\Components\TextInput::make('min_cart_amount')
                    ->numeric()
                    ->rules(['min:0']),
                Forms\Components\TextInput::make('max_cart_amount')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('valid_from')
                    ->required(),
                Forms\Components\DateTimePicker::make('valid_to')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_cart_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_from')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_to')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}