<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'firebase_uid', 'role', 'phone', 'is_disabled'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_disabled' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->is_disabled) {
            return false;
        }

        return in_array($this->role, [UserRole::Admin, UserRole::Responder], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isResponder(): bool
    {
        return $this->role === UserRole::Responder;
    }

    public function firebaseUid(): string
    {
        return $this->firebase_uid ?: (string) $this->id;
    }
}
