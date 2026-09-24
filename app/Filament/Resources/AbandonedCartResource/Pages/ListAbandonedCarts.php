<?php

namespace App\Filament\Resources\AbandonedCartResource\Pages;

use App\Filament\Resources\AbandonedCartResource;
use App\Filament\Resources\AbandonedCartResource\Widgets\AbandonedCartOverview;
use App\Models\Setting;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListAbandonedCarts extends ListRecords
{
    protected static string $resource = AbandonedCartResource::class;

    public function mount(): void
    {
        parent::mount();

        // Automatically detect and sync fresh abandoned carts whenever page loads
        Artisan::call('carts:check-abandoned');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AbandonedCartOverview::class,
        ];
    }

    public function getSubheading(): ?string
    {
        $minutes = (int) Setting::get('abandoned_cart_wait_time_minutes', 60);
        return "Carts inactive for more than {$minutes} minutes are automatically tracked here.";
    }

    protected function getHeaderActions(): array
    {
        return [
            // 1. Settings Action (Wait time threshold: 20 min, 30 min, 60 min, custom)
            Actions\Action::make('settings')
                ->label('Settings')
                ->color('gray')
                ->modalHeading('Abandoned Cart Wait Time Settings')
                ->modalSubheading('Configure how much time to wait before marking an inactive shopping cart as Abandoned.')
                ->modalSubmitActionLabel('Save Settings')
                ->form([
                    Forms\Components\Select::make('preset_minutes')
                        ->label('Inactivity Threshold')
                        ->options([
                            '20' => '20 Minutes',
                            '30' => '30 Minutes',
                            '60' => '60 Minutes (1 Hour - Default)',
                            '120' => '120 Minutes (2 Hours)',
                            'custom' => 'Custom Minutes',
                        ])
                        ->default(function () {
                            $val = (string) Setting::get('abandoned_cart_wait_time_minutes', '60');
                            return in_array($val, ['20', '30', '60', '120']) ? $val : 'custom';
                        })
                        ->reactive()
                        ->required(),

                    Forms\Components\TextInput::make('custom_minutes')
                        ->label('Custom Wait Time (Minutes)')
                        ->numeric()
                        ->minValue(5)
                        ->maxValue(10080)
                        ->default(fn () => (int) Setting::get('abandoned_cart_wait_time_minutes', 60))
                        ->visible(fn (Forms\Get $get): bool => $get('preset_minutes') === 'custom')
                        ->required(fn (Forms\Get $get): bool => $get('preset_minutes') === 'custom'),
                ])
                ->action(function (array $data): void {
                    $minutes = $data['preset_minutes'] === 'custom'
                        ? (int) ($data['custom_minutes'] ?? 60)
                        : (int) $data['preset_minutes'];

                    if ($minutes < 5) {
                        $minutes = 5;
                    }

                    Setting::set('abandoned_cart_wait_time_minutes', (string) $minutes, 'sales');

                    Notification::make()
                        ->title('Settings Saved')
                        ->body("Abandoned cart threshold updated to {$minutes} minutes.")
                        ->success()
                        ->send();
                }),

            // 2. Sync / Refresh Carts Action
            Actions\Action::make('syncCarts')
                ->label('Sync Carts')
                ->color('primary')
                ->action(function (): void {
                    Artisan::call('carts:check-abandoned');

                    Notification::make()
                        ->title('Sync Complete')
                        ->body('All inactive carts have been checked and synchronized.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
