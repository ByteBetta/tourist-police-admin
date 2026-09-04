<?php

namespace App\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Firebase\DocumentStore;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\AuthException;
use Throwable;

class UserRepository
{
    public function __construct(protected DocumentStore $store) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function all(?string $role = null, ?string $search = null): array
    {
        $users = collect($this->store->list('users'))->map(fn (array $user): array => $this->normalize($user));

        if ($role) {
            $users = $users->where('role', $role);
        }

        if ($search) {
            $needle = Str::lower($search);
            $users = $users->filter(function (array $user) use ($needle): bool {
                return str_contains(Str::lower($user['name'].' '.$user['email'].' '.$user['phone']), $needle);
            });
        }

        return $users
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function responders(): array
    {
        return $this->all(UserRole::Responder->value);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        $user = $this->store->get('users', $id);

        return $user ? $this->normalize($user) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        return collect($this->all())
            ->first(fn (array $user): bool => strcasecmp((string) $user['email'], $email) === 0);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createStaff(array $data): array
    {
        $uid = $this->createFirebaseAuthUser($data) ?? (string) Str::uuid();

        $profile = $this->store->put('users', $uid, [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'] ?? UserRole::Responder->value,
            'verified' => true,
            'disabled' => false,
            'createdAt' => now()->toIso8601String(),
        ]);

        User::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'firebase_uid' => $uid,
                'role' => $data['role'] ?? UserRole::Responder->value,
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'] ?? Str::password(16),
                'is_disabled' => false,
            ],
        );

        return $this->normalize($profile);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $id, array $data): array
    {
        $profile = $this->store->patch('users', $id, $data);

        User::query()->where('firebase_uid', $id)->update([
            'name' => $profile['name'] ?? $data['name'] ?? null,
            'role' => $profile['role'] ?? $data['role'] ?? UserRole::Tourist->value,
            'phone' => $profile['phone'] ?? $data['phone'] ?? null,
            'is_disabled' => (bool) ($profile['disabled'] ?? $data['disabled'] ?? false),
        ]);

        if (isset($data['disabled'])) {
            $this->setFirebaseDisabled($id, (bool) $data['disabled']);
        }

        return $this->normalize($profile);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function firebaseAuth(): ?FirebaseAuth
    {
        if (! is_file((string) config('services.firebase.credentials'))) {
            return null;
        }

        try {
            return app('firebase.auth');
        } catch (Throwable) {
            return null;
        }
    }

    protected function createFirebaseAuthUser(array $data): ?string
    {
        $auth = $this->firebaseAuth();

        if (! $auth) {
            return null;
        }

        try {
            $user = $auth->createUser([
                'email' => $data['email'],
                'password' => $data['password'],
                'displayName' => $data['name'],
                'disabled' => false,
            ]);

            return $user->uid;
        } catch (Throwable) {
            try {
                return $auth->getUserByEmail($data['email'])->uid;
            } catch (Throwable) {
                return null;
            }
        }
    }

    protected function setFirebaseDisabled(string $uid, bool $disabled): void
    {
        $auth = $this->firebaseAuth();

        if (! $auth) {
            return;
        }

        try {
            $auth->updateUser($uid, ['disabled' => $disabled]);
        } catch (AuthException|Throwable) {
            // Local/demo mode or missing Auth user.
        }
    }

    /**
     * @param  array<string, mixed>  $user
     * @return array<string, mixed>
     */
    protected function normalize(array $user): array
    {
        $id = (string) ($user['id'] ?? $user['__key'] ?? Str::uuid());

        return [
            '__key' => $id,
            'id' => $id,
            'name' => $user['name'] ?? 'Unnamed user',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'role' => $user['role'] ?? UserRole::Tourist->value,
            'verified' => (bool) ($user['verified'] ?? false),
            'disabled' => (bool) ($user['disabled'] ?? false),
            'fcmToken' => $user['fcmToken'] ?? null,
            'createdAt' => $user['createdAt'] ?? null,
        ];
    }
}
