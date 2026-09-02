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
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->vendor) {
            $vendor = $this->record->vendor;
            
            $data['vendor'] = [
                'vendor_name' => $vendor->vendor_name,
                'contact_person' => $vendor->contact_person,
                'vendor_logo' => $vendor->vendor_logo,
                'isbn_number' => $vendor->isbn_number,
                'pan_number' => $vendor->pan_number,
                'gst_number' => $vendor->gst_number,
                'vendor_phone' => $vendor->vendor_phone,
                'vendor_address' => $vendor->vendor_address,
                'city' => $vendor->city,
                'state' => $vendor->state,
                'pincode' => $vendor->pincode,
                'vendor_website' => $vendor->vendor_website,
                'bank_name' => $vendor->bank_name,
                'account_holder_name' => $vendor->account_holder_name,
                'account_number' => $vendor->account_number,
                'ifsc_code' => $vendor->ifsc_code,
                'upi_id' => $vendor->upi_id,
                'commission_percentage' => $vendor->commission_percentage ?? 7.00,
                'approval_status' => $vendor->approval_status ?? 'approved',
            ];
        }

        return $data;
    }

    /**
     * 🔥 Save se pehle vendor data handle karein
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['role']) && $data['role'] === 'vendor') {
            if (isset($data['vendor'])) {
                $vendorData = $data['vendor'];
                
                if (!isset($vendorData['commission_percentage']) || $vendorData['commission_percentage'] === '') {
                    $vendorData['commission_percentage'] = 7.00;
                }

                if ($this->record->vendor) {
                    $this->record->vendor->update($vendorData);
                } else {
                    $vendorData['user_id'] = $this->record->id;
                    Vendor::create($vendorData);
                }
            }
            
            unset($data['vendor']);
        }
        
        return $data;
    }
}