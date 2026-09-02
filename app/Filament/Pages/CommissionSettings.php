<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class CommissionSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Commission Settings';
    protected static ?string $title = 'Global Commission Settings';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.commission-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'vendor_commission_percentage' => Setting::getVendorCommission(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Global Platform Commission')
                    ->description('Set a single commission percentage that applies across all vendors automatically.')
                    ->schema([
                        TextInput::make('vendor_commission_percentage')
                            ->label('Platform Commission Percentage (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required()
                            ->helperText('This commission percentage is automatically applied to all vendor orders. Changing this value here instantly updates all vendors globally.'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Commission Setting')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $rate = (float) ($data['vendor_commission_percentage'] ?? 7.00);

        Setting::set('vendor_commission_percentage', (string) $rate);

        Notification::make()
            ->title('Commission Setting Saved')
            ->body("Global vendor commission has been set to {$rate}%. All vendor accounts and orders will now use this rate.")
            ->success()
            ->send();
    }
}
