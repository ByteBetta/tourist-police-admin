<?php

namespace App\Services\Firebase;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FirebaseAuthService
{
    public function __construct(protected UserRepository $users) {}

    public function attempt(string $email, string $password): ?User
    {
        if ($this->hasRemoteAuth()) {
            $uid = $this->signInWithPassword($email, $password);

            if (! $uid) {
                return null;
            }

            $profile = $this->users->find($uid) ?? $this->users->findByEmail($email);

            if (! $profile || ! in_array($profile['role'], [UserRole::Admin->value, UserRole::Responder->value], true)) {
                return null;
            }

            if ($profile['disabled'] ?? false) {
                return null;
            }

            return $this->syncLocalUser($profile, $uid);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user || $user->is_disabled || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()) ? $user : null;
    }

    public function hasRemoteAuth(): bool
    {
        return filled(config('services.firebase.api_key'))
            && is_file((string) config('services.firebase.credentials'));
    }

    protected function signInWithPassword(string $email, string $password): ?string
    {
        $response = Http::asJson()->post(
            'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key='.config('services.firebase.api_key'),
            [
                'email' => $email,
                'password' => $password,
                'returnSecureToken' => true,
            ],
        );

        if (! $response->successful()) {
            return null;
        }

        return $response->json('localId');
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    protected function syncLocalUser(array $profile, string $uid): User
    {
        return User::query()->updateOrCreate(
            ['firebase_uid' => $uid],
            [
                'name' => $profile['name'] ?? 'Staff member',
                'email' => $profile['email'],
                'role' => $profile['role'],
                'phone' => $profile['phone'] ?? null,
                'is_disabled' => (bool) ($profile['disabled'] ?? false),
                'password' => Str::password(24),
            ],
        );
    }
}
