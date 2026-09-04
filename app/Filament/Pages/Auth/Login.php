<?php

namespace App\Filament\Pages\Auth;

use App\Services\Firebase\FirebaseAuthService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;

class Login extends BaseLogin
{
    protected static bool $isDiscovered = false;

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $user = app(FirebaseAuthService::class)->attempt($data['email'], $data['password']);

        if (! $user || ! $this->isUserAllowedToAccessPanel($user)) {
            $this->throwFailureValidationException();
        }

        Filament::auth()->login($user, $data['remember'] ?? false);
        session()->regenerate();

        return app(LoginResponse::class);
    }
}
