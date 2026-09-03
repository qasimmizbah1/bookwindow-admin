<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        // 1. Check if user account is active
        if (! $user->is_active) {
            Filament::auth()->logout();

            throw ValidationException::withMessages([
                'data.email' => 'Your account is currently inactive. Please contact the administrator.',
            ]);
        }

        // 2. For Vendors: check vendor profile and approval status
        if ($user->isVendor()) {
            $vendor = $user->vendor;

            if (! $vendor || $vendor->approval_status !== 'approved') {
                Filament::auth()->logout();

                $message = match ($vendor?->approval_status) {
                    'pending' => 'Your vendor account registration is pending admin approval. You will be able to log in once your account has been approved.',
                    'suspended' => 'Your vendor account has been suspended. Please contact the administrator for assistance.',
                    default => 'Your vendor account is not authorized to access the panel.',
                };

                throw ValidationException::withMessages([
                    'data.email' => $message,
                ]);
            }
        }

        // 3. Filament panel access check
        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
}
