<?php

namespace App\Filament\Resources\PaymentLogResource\Pages;

use App\Filament\Resources\PaymentLogResource;
use App\Models\Order;
use App\Services\RazorpayService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ListPaymentLogs extends ListRecords
{
    protected static string $resource = PaymentLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. Test Gateway Connection
            // Action::make('testConnection')
            //     ->label('Test Gateway Connection')
            //     ->icon('heroicon-o-bolt')
            //     ->color('gray')
            //     ->action(function () {
            //         $key = config('services.razorpay.key') ?: env('RAZORPAY_KEY');
            //         $secret = config('services.razorpay.secret') ?: env('RAZORPAY_SECRET');

            //         if (empty($key) || empty($secret)) {
            //             Notification::make()
            //                 ->title('Missing Credentials')
            //                 ->body('RAZORPAY_KEY or RAZORPAY_SECRET is not configured in .env.')
            //                 ->danger()
            //                 ->send();
            //             return;
            //         }

            //         try {
            //             $service = new RazorpayService();
            //             $orders = $service->getApi()->order->all(['count' => 1]);
            //             $maskedKey = substr($key, 0, 8) . '...' . substr($key, -4);

            //             Notification::make()
            //                 ->title('Razorpay Connected Successfully!')
            //                 ->body("API is responding. Key: {$maskedKey} (Mode: " . (str_starts_with($key, 'rzp_test') ? 'TEST MODE' : 'LIVE MODE') . ")")
            //                 ->success()
            //                 ->send();
            //         } catch (\Exception $e) {
            //             Notification::make()
            //                 ->title('Razorpay Connection Failed')
            //                 ->body($e->getMessage())
            //                 ->danger()
            //                 ->send();
            //         }
            //     }),

            // 2. Create Test Order on Razorpay
            // Action::make('createTestOrder')
            //     ->label('Create Test Order')
            //     ->icon('heroicon-o-plus-circle')
            //     ->color('info')
            //     ->modalHeading('Create Test Razorpay Order')
            //     ->modalDescription('Creates a test order in the local database and generates a real Razorpay Order ID on Razorpay using configured test keys. It verifies that notes and receipt are properly sent.')
            //     ->form([
            //         TextInput::make('amount')
            //             ->label('Order Amount (INR)')
            //             ->numeric()
            //             ->default(100)
            //             ->required(),
            //         TextInput::make('customer_name')
            //             ->label('Customer Name')
            //             ->default('Test Customer')
            //             ->required(),
            //         TextInput::make('phone')
            //             ->label('Phone Number')
            //             ->default('9876543210')
            //             ->required(),
            //         TextInput::make('email')
            //             ->label('Email Address')
            //             ->default('test@bookwindow.in')
            //             ->email()
            //             ->required(),
            //     ])
            //     ->action(function (array $data) {
            //         try {
            //             $nameParts = explode(' ', trim($data['customer_name']), 2);
            //             $firstName = $nameParts[0] ?? 'Test';
            //             $lastName = $nameParts[1] ?? 'User';

            //             $orderNumberPlaceholder = 'OR-TEST-' . time();

            //             $order = Order::create([
            //                 'order_number' => $orderNumberPlaceholder,
            //                 'first_name' => $firstName,
            //                 'last_name' => $lastName,
            //                 'customer_phone' => '+91' . preg_replace('/\D/', '', $data['phone']),
            //                 'email' => $data['email'],
            //                 'subtotal' => $data['amount'],
            //                 'shipping_amount' => 0.00,
            //                 'discount_amount' => 0.00,
            //                 'tax_amount' => 0.00,
            //                 'delivery_amount' => 0.00,
            //                 'total_amount' => $data['amount'],
            //                 'shipping_method' => 'Standard Shipping',
            //                 'payment_method' => 'razorpay',
            //                 'payment_status' => 'payment_pending',
            //                 'status' => 'payment_pending',
            //                 'address' => 'Test Address, Local Testing',
            //                 'city' => 'Jaipur',
            //                 'state' => 'Rajasthan',
            //                 'zip_code' => '302001',
            //                 'country' => 'India',
            //                 'session_id' => null,
            //             ]);

            //             $order->update([
            //                 'order_number' => 'OR-' . $order->id,
            //             ]);

            //             $service = new RazorpayService();
            //             $razorpayOrder = $service->createOrder($order, $order->order_number);

            //             $order->update([
            //                 'razorpay_order_id' => $razorpayOrder->id,
            //                 'payment_status' => 'created',
            //             ]);

            //             Notification::make()
            //                 ->title('Test Order Created on Razorpay!')
            //                 ->body("Order #{$order->order_number} created with Razorpay Order ID: {$razorpayOrder->id}. Notes and receipt were successfully attached.")
            //                 ->success()
            //                 ->send();

            //         } catch (\Exception $e) {
            //             Notification::make()
            //                 ->title('Failed to Create Test Order')
            //                 ->body($e->getMessage())
            //                 ->danger()
            //                 ->send();
            //         }
            //     }),

            // 3. Run Razorpay Sync Artisan Command
            // Action::make('runSyncCommand')
            //     ->label('Run Razorpay Sync')
            //     ->icon('heroicon-o-arrow-path')
            //     ->color('warning')
            //     ->modalHeading('Run Razorpay Sync (orders:sync-razorpay)')
            //     ->modalDescription('Trigger the payment recovery reconciliation command directly from the admin panel.')
            //     ->form([
            //         TextInput::make('minutes')
            //             ->label('Older than (minutes)')
            //             ->numeric()
            //             ->default(0)
            //             ->helperText('Set to 0 to check all pending orders immediately for testing (Default cron uses 10 mins).'),
            //         TextInput::make('order')
            //             ->label('Target Specific Order (Optional)')
            //             ->placeholder('e.g. 58782 or OR-58782')
            //             ->helperText('Leave empty to check all pending orders.'),
            //         Toggle::make('dry_run')
            //             ->label('Dry Run Mode')
            //             ->helperText('When enabled, checks Razorpay status without altering the database.')
            //             ->default(false),
            //     ])
            //     ->action(function (array $data) {
            //         try {
            //             $params = [
            //                 '--minutes' => (int)($data['minutes'] ?? 0),
            //                 '--limit' => 50,
            //             ];

            //             if (!empty($data['order'])) {
            //                 $params['--order'] = $data['order'];
            //             }

            //             if (!empty($data['dry_run'])) {
            //                 $params['--dry-run'] = true;
            //             }

            //             Artisan::call('orders:sync-razorpay', $params);
            //             $output = Artisan::output();

            //             Notification::make()
            //                 ->title('Sync Command Finished')
            //                 ->body($output ?: 'Sync completed with no output.')
            //                 ->success()
            //                 ->send();

            //         } catch (\Exception $e) {
            //             Notification::make()
            //                 ->title('Sync Command Failed')
            //                 ->body($e->getMessage())
            //                 ->danger()
            //                 ->send();
            //         }
            //     }),

            // 4. Simulate Webhook / Recovery
            // Action::make('simulateWebhook')
            //     ->label('Simulate Webhook')
            //     ->icon('heroicon-o-command-line')
            //     ->color('success')
            //     ->modalHeading('Simulate Razorpay Webhook (payment.captured)')
            //     ->modalDescription('Simulates an incoming Razorpay payment.captured webhook event for any pending order to verify the recovery workflow.')
            //     ->form([
            //         Select::make('order_id')
            //             ->label('Select Pending Razorpay Order')
            //             ->options(function () {
            //                 return Order::where('payment_method', 'razorpay')
            //                     ->whereIn('payment_status', ['payment_pending', 'created'])
            //                     ->whereNotNull('razorpay_order_id')
            //                     ->latest()
            //                     ->take(20)
            //                     ->get()
            //                     ->mapWithKeys(fn ($o) => [$o->id => "#{$o->order_number} (ID: {$o->id}, RZP: {$o->razorpay_order_id}) - ₹{$o->total_amount}"]);
            //             })
            //             ->required()
            //             ->searchable(),
            //         TextInput::make('payment_id')
            //             ->label('Mock Razorpay Payment ID')
            //             ->default('pay_test_sim_' . time())
            //             ->required(),
            //     ])
            //     ->action(function (array $data) {
            //         try {
            //             $order = Order::findOrFail($data['order_id']);
            //             $service = new RazorpayService();

            //             $mockPayload = [
            //                 'entity' => 'event',
            //                 'account_id' => 'acc_test',
            //                 'event' => 'payment.captured',
            //                 'payload' => [
            //                     'payment' => [
            //                         'entity' => [
            //                             'id' => $data['payment_id'],
            //                             'order_id' => $order->razorpay_order_id,
            //                             'amount' => (int)($order->total_amount * 100),
            //                             'status' => 'captured',
            //                             'notes' => [
            //                                 'order_id' => (string)$order->id,
            //                                 'order_number' => (string)$order->order_number,
            //                             ],
            //                         ],
            //                     ],
            //                 ],
            //             ];

            //             $result = $service->markOrderAsPaid($order, $data['payment_id'], 'webhook', $mockPayload);

            //             Notification::make()
            //                 ->title('Webhook Simulation Succeeded!')
            //                 ->body("Order #{$order->order_number} marked as Paid. Logged to Payment Logs.")
            //                 ->success()
            //                 ->send();

            //         } catch (\Exception $e) {
            //             Notification::make()
            //                 ->title('Simulation Failed')
            //                 ->body($e->getMessage())
            //                 ->danger()
            //                 ->send();
            //         }
            //     }),
        ];
    }
}
