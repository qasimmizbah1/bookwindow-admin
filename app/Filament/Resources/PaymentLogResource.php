<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentLogResource\Pages;
use App\Models\PaymentLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;

class PaymentLogResource extends Resource
{
    protected static ?string $model = PaymentLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?string $navigationLabel = 'Payment Logs';

    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission('payment_logs') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('order_number')
                    ->label('Order Number'),
                TextInput::make('gateway')
                    ->label('Payment Gateway'),
                TextInput::make('event_type')
                    ->label('Event Type'),
                TextInput::make('status')
                    ->label('Status'),
                TextInput::make('razorpay_order_id')
                    ->label('Razorpay Order ID'),
                TextInput::make('razorpay_payment_id')
                    ->label('Razorpay Payment ID'),
                TextInput::make('amount')
                    ->label('Amount (INR)'),
                DateTimePicker::make('created_at')
                    ->label('Logged At'),
                Textarea::make('message')
                    ->label('Message / Reason')
                    ->columnSpanFull()
                    ->rows(3),
                Textarea::make('formatted_payload')
                    ->label('Raw Payload (JSON)')
                    ->columnSpanFull()
                    ->rows(10)
                    ->formatStateUsing(fn ($record) => $record && $record->payload ? json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : ''),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('M d, Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->colors([
                        'info' => 'callback',
                        'purple' => 'webhook',
                        'warning' => 'cron_sync',
                        'primary' => 'manual_recovery',
                        'gray' => 'order_created',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'callback' => 'Callback',
                        'webhook' => 'Webhook',
                        'cron_sync' => 'Cron Sync',
                        'manual_recovery' => 'Manual Recover',
                        'order_created' => 'Checkout Init',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(function ($state, $record) {
                        if ($record && $record->event_type === 'order_created' && in_array($state, ['success', 'initiated'])) {
                            return 'info';
                        }
                        return match ($state) {
                            'success' => 'success',
                            'initiated' => 'info',
                            'pending' => 'warning',
                            'cancelled' => 'warning',
                            'failed', 'error' => 'danger',
                            'skipped' => 'gray',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if ($record && $record->event_type === 'order_created' && $state === 'success') {
                            return 'Initiated';
                        }
                        return match ($state) {
                            'initiated' => 'Initiated',
                            'pending' => 'Pending',
                            'success' => 'Success',
                            'failed' => 'Failed',
                            'cancelled' => 'Cancelled',
                            'skipped' => 'Skipped',
                            'error' => 'Error',
                            default => ucfirst($state),
                        };
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('razorpay_order_id')
                    ->label('Razorpay Order ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('razorpay_payment_id')
                    ->label('Razorpay Payment ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('message')
                    ->label('Log Message')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->message)
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->label('Event Type')
                    ->options([
                        'callback' => 'Callback',
                        'webhook' => 'Webhook',
                        'cron_sync' => 'Cron Sync',
                        'manual_recovery' => 'Manual Recovery',
                        'order_created' => 'Checkout Initiated',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'success' => 'Success (Paid)',
                        'initiated' => 'Initiated',
                        'cancelled' => 'Cancelled',
                        'failed' => 'Failed',
                        'pending' => 'Pending',
                        'skipped' => 'Skipped',
                        'error' => 'Error',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Details'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->isAdmin() ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentLogs::route('/'),
        ];
    }
}
