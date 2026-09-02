<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Vendor;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        // Only create vendor if role is vendor
        if (($this->data['role'] ?? '') === 'vendor') {
            $vendorData = $this->data['vendor'] ?? [];
            $vendorData['user_id'] = $this->record->id;

            if (!isset($vendorData['commission_percentage']) || $vendorData['commission_percentage'] === '') {
                $vendorData['commission_percentage'] = 7.00;
            }

            if (!isset($vendorData['approval_status']) || empty($vendorData['approval_status'])) {
                $vendorData['approval_status'] = 'approved';
            }

            Vendor::create($vendorData);
        }
    }
}
