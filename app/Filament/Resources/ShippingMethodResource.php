<?php
// app/Filament/Resources/ShippingMethodResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\ShippingMethodResource\Pages;
use App\Filament\Resources\ShippingMethodResource\RelationManagers;
use App\Models\ShippingMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';


    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = "Shop";


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Update Information')
                ->schema([
                    Forms\Components\Placeholder::make('last_updated_at')
                        ->label('Last Updated')
                        ->content(function (?ShippingMethod $record) {
                            return $record?->updated_at
                                ? $record->updated_at->format('d M Y, h:i A')
                                : 'Not updated yet';
                        }),

                    Forms\Components\Placeholder::make('updated_by')
                        ->label('Updated By')
                        ->content(function (?ShippingMethod $record) {
                            return $record?->updatedBy?->name ?? 'Not available';
                        }),
                ])
                ->columns(2)
                ->visible(fn (string $operation) => $operation === 'edit'),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit' => Pages\EditShippingMethod::route('/{record}/edit'),
        ];
    }
}