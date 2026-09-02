<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Add your form fields here if needed
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.email')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items.total')
                    ->label('Your Earnings')
                    ->sortable()
                    
            ])
            ->filters([
                // Add filters if needed
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            // Add relation managers if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $vendor = \App\Models\Vendor::where('user_id', auth()->id())->first();
        
        if ($vendor) {
            return static::getEloquentQuery()->count();
        }

        return null;
    }

    public static function getEloquentQuery(): Builder
    {
        $vendor = \App\Models\Vendor::where('user_id', auth()->id())->first();
        
        $query = parent::getEloquentQuery();
        
        if ($vendor) {
            return $query->whereHas('items', function ($query) use ($vendor) {
                $query->where('vendor_id', $vendor->id);
            });
        }

        return $query;
    }

    // Add a method to calculate vendor-specific total
    public static function getVendorTotal($order)
    {
        $vendor = \App\Models\Vendor::where('user_id', auth()->id())->first();
        
        if ($vendor) {
            return $order->vendorItems($vendor->id)->sum('price');
        }

        return 0;
    }
}