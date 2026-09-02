<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Order;
use App\Filament\Resources\OrderResource;
use Illuminate\Database\Eloquent\Builder;

class VendorLatestOrders extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected int $perPage = 5;

    public static function canView(): bool
    {
        return auth()->user()?->isVendor() ?? false;
    }

    public function table(Table $table): Table
    {
        $vendor = auth()->user()?->vendor;

        return $table
            ->heading('My Recent Orders')
            ->query(
                OrderResource::getEloquentQuery()
            )
            ->defaultPaginationPageOption($this->perPage)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label('ID'),

                Tables\Columns\TextColumn::make('order_number')
                    ->sortable()
                    ->searchable()
                    ->label('Order #'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Customer')
                    ->getStateUsing(fn ($record) => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) ?: 'Customer'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vendor_items_count')
                    ->label('Your Items')
                    ->getStateUsing(function ($record) use ($vendor) {
                        if (!$vendor) return 0;
                        return $record->items->where('vendor_id', $vendor->id)->sum('quantity') . ' item(s)';
                    }),

                Tables\Columns\TextColumn::make('vendor_amount')
                    ->label('Net Earnings')
                    ->getStateUsing(function ($record) use ($vendor) {
                        if (!$vendor) return '₹0.00';
                        $commissionRate = $vendor->commission_rate;
                        $grossSum = $record->items->where('vendor_id', $vendor->id)->sum(function ($item) {
                            return ($item->quantity ?? 1) * ($item->price ?? 0);
                        });
                        $netSum = $grossSum * (1 - ($commissionRate / 100));
                        return '₹' . number_format($netSum, 2);
                    })
                    ->description(function ($record) use ($vendor) {
                        if (!$vendor) return '';
                        return "after {$vendor->commission_rate}% fee";
                    })
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'gray',
                        'pending' => 'warning',
                        'processing' => 'info',
                        'order_shipped' => 'primary',
                        'completed' => 'success',
                        'cancelled', 'declined' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View / Fulfill')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('id', 'desc');
    }
}
