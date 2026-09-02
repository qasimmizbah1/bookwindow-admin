<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Vendor;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * 🔥 Form load karte waqt vendor data fill karein
     * FORM FIELD: vendor.vendor_name → $data['vendor']['vendor_name']
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Agar vendor exist karta hai toh saari fields fill karein
        if ($this->record->vendor) {
            $vendor = $this->record->vendor;
            
            // 🔥 IMPORTANT: 'vendor' array mein saari fields daalein
            $data['vendor'] = [
                'vendor_name' => $vendor->vendor_name,
                'vendor_logo' => $vendor->vendor_logo,
                'isbn_number' => $vendor->isbn_number,
                'vendor_phone' => $vendor->vendor_phone,
                'vendor_address' => $vendor->vendor_address,
                'vendor_website' => $vendor->vendor_website,
            ];
        }

        return $data;
    }

    /**
     * 🔥 Save se pehle vendor data handle karein
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Agar role vendor hai toh vendor data save karein
        if (isset($data['role']) && $data['role'] === 'vendor') {
            
            // Agar vendor data form mein 'vendor' array mein hai
            if (isset($data['vendor'])) {
                $vendorData = $data['vendor'];
                
                // Agar vendor exist karta hai toh update karein
                if ($this->record->vendor) {
                    $this->record->vendor->update($vendorData);
                } else {
                    $vendorData['user_id'] = $this->record->id;
                    Vendor::create($vendorData);
                }
            }
            
            // 🔥 Vendor data ko main data se hatao (user table mein save na ho)
            unset($data['vendor']);
        }
        
        return $data;
    }
}