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
        if ($this->data['role'] === 'vendor') {
            Vendor::create([
                'user_id' => $this->record->id,
                'vendor_name' => $this->data['vendor_name'],
            ]);
        }
    }
}
