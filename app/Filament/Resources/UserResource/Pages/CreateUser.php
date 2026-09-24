<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Vendor;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected array $vendorDataToSave = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['vendor'])) {
            $this->vendorDataToSave = $data['vendor'];
            unset($data['vendor']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Only create vendor if role is vendor
        if (($this->record->role ?? '') === 'vendor') {
            $vendorData = $this->vendorDataToSave;
            $vendorData['user_id'] = $this->record->id;

            if (!isset($vendorData['approval_status']) || empty($vendorData['approval_status'])) {
                $vendorData['approval_status'] = 'approved';
            }

            Vendor::create($vendorData);
        }
    }
}
