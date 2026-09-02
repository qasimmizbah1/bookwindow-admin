<?php

namespace App\Filament\Vendor\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('Yours Products')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        $vendor = \App\Models\Vendor::where('user_id', auth()->id())->first();

        return $table
            ->recordTitleAttribute('Yours Products')
            ->modifyQueryUsing(function (Builder $query) {
            if ($vendor) {
            $query->where('vendor_id', $vendor->id);
            }
            })
           ->columns([
            Tables\Columns\TextColumn::make('product.name'),
            Tables\Columns\TextColumn::make('quantity'),
            Tables\Columns\TextColumn::make('price')
                ->money('INR'),
            Tables\Columns\TextColumn::make('total')
                ->money('INR')
                ->state(function ($record) {
                    return $record->price * $record->quantity;
                }),
        ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
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
}
