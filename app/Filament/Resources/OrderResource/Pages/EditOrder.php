<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function getHeading(): string
    {
        return 'Edit Order #' . ($this->record->order_number ?? $this->record->id);
    }

    public function getSubheading(): ?string
    {
        $name = trim(($this->record->first_name ?? '') . ' ' . ($this->record->last_name ?? ''));
        $phone = $this->record->customer_phone ?? '';
        $amount = format_currency($this->record->total_amount ?? 0, 2);

        $parts = array_filter([$name, $phone ? "📞 {$phone}" : null, "Grand Total: {$amount}"]);
        return implode('  •  ', $parts);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('orders.pdf', $this->record->id))
                ->openUrlInNewTab(),
            Actions\Action::make('printInvoice')
                ->label('Print Invoice')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => "javascript:window.open('" . route('orders.print', $this->record->id) . "', 'Print', 'width=800,height=600'); void(0);"),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data['status'] ?? '', ['cancelled', 'declined'])) {
            if (empty($this->record->cancelled_by)) {
                $data['cancelled_by'] = 'admin';
            }
        }

        if (!empty($data['call_status']) && $data['call_status'] !== 'pending') {
            if (empty($this->record->called_at)) {
                $data['called_at'] = now();
            }
        }

        return $data;
    }
}
