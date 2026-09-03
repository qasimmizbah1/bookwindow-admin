<?php

namespace App\Filament\Resources\VendorProfileResource\Pages;

use App\Filament\Resources\VendorProfileResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EditVendorProfile extends EditRecord
{
    protected static string $resource = VendorProfileResource::class;

    protected static ?string $title = 'Store Profile & Settings';

    public function mount(int | string $record = null): void
    {
        $vendorId = auth()->user()?->vendor?->id;

        if (! $vendorId) {
            abort(403, 'Vendor profile not found.');
        }

        parent::mount($vendorId);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = auth()->user();
        $data['user_name'] = $user->name;
        $data['user_email'] = $user->email;

        return $data;
    }

    protected function beforeSave(): void
    {
        $data = $this->data;
        $user = auth()->user();

        // Password verification if user wants to change password
        if (! empty($data['new_password'])) {
            if (empty($data['current_password']) || ! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'data.current_password' => 'Your current password does not match our records.',
                ]);
            }

            if (strlen($data['new_password']) < 8) {
                throw ValidationException::withMessages([
                    'data.new_password' => 'New password must be at least 8 characters long.',
                ]);
            }

            if ($data['new_password'] !== ($data['new_password_confirmation'] ?? '')) {
                throw ValidationException::withMessages([
                    'data.new_password_confirmation' => 'Password confirmation does not match.',
                ]);
            }

            $user->password = Hash::make($data['new_password']);
        }

        // Update user account name
        if (! empty($data['user_name'])) {
            $user->name = $data['user_name'];
        }

        $user->save();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['user_name']);
        unset($data['user_email']);
        unset($data['current_password']);
        unset($data['new_password']);
        unset($data['new_password_confirmation']);
        unset($data['pan_number']);
        unset($data['gst_number']);
        unset($data['isbn_number']);
        unset($data['approval_status']);
        unset($data['commission_percentage']);

        if (isset($data['ifsc_code'])) {
            $data['ifsc_code'] = strtoupper($data['ifsc_code']);
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Profile Updated Successfully')
            ->body('Your store profile and settings have been saved.');
    }
}
