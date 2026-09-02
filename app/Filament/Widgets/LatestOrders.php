<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Order;
use App\Filament\Resources\OrderResource;

class LatestOrders extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 6;
    
    protected int $perPage = 5;

    public function table(Table $table): Table
    {
        return $table
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
                    
                Tables\Columns\TextColumn::make('razorpay_payment_id')
                    ->searchable()
                    ->label('Payment ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Customer Name')
                   ->getStateUsing(function ($record) {
                        return ($record->customername->first_name ?? 'User') . ' ' . ($record->customername->last_name ?? 'User');
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
            ])
            ->defaultSort('id', 'desc')
            ->deferLoading();
    }
}