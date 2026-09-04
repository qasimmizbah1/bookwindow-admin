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
            'vendor_commission_gst_percentage' => Setting::getCommissionGst(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Global Platform Commission & GST')
                    ->description('Configure platform commission fee and applicable GST on platform services.')
                    ->schema([
                        TextInput::make('vendor_commission_percentage')
                            ->label('Platform Commission Percentage (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required()
                            ->helperText('Default commission percentage applied to vendor orders (can also be customized per vendor in User management).'),

                        TextInput::make('vendor_commission_gst_percentage')
                            ->label('GST on Platform Commission (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required()
                            ->default(18.00)
                            ->helperText('Standard GST percentage applicable on platform service fee (e.g. 18%). Deducted alongside commission from vendor order payouts.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Commission & GST Settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $rate = (float) ($data['vendor_commission_percentage'] ?? 7.00);
        $gstRate = (float) ($data['vendor_commission_gst_percentage'] ?? 18.00);

        Setting::set('vendor_commission_percentage', (string) $rate);
        Setting::set('vendor_commission_gst_percentage', (string) $gstRate);

        Notification::make()
            ->title('Commission & GST Settings Saved')
            ->body("Global vendor commission has been set to {$rate}% with {$gstRate}% GST on commission fees.")
            ->success()
            ->send();
    }
}
