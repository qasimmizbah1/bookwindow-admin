<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Products')
                ->badge(fn () => static::getResource()::getEloquentQuery()->count()),

            'out_of_stock' => Tab::make('Out of Stock')
                ->badge(fn () => static::getResource()::getEloquentQuery()
                    ->where(fn (Builder $q) => $q->whereNull('quantity')->orWhere('quantity', '<=', 0))
                    ->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(fn (Builder $q) => $q->whereNull('quantity')->orWhere('quantity', '<=', 0))),

            'lowest_stock' => Tab::make('Lowest Stock First')
                ->icon('heroicon-m-bars-arrow-up')
                ->modifyQueryUsing(fn (Builder $query) => $query->reorder()->orderByRaw('COALESCE(quantity, 0) ASC')),

            'low_stock' => Tab::make('Low Stock (1-5)')
                ->badge(fn () => static::getResource()::getEloquentQuery()
                    ->where('quantity', '>', 0)
                    ->where('quantity', '<=', 5)
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('quantity', '>', 0)->where('quantity', '<=', 5)),

            'in_stock' => Tab::make('In Stock')
                ->badge(fn () => static::getResource()::getEloquentQuery()
                    ->where('quantity', '>', 0)
                    ->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('quantity', '>', 0)),
        ];
    }
}
