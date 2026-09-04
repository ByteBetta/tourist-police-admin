<?php

namespace App\Filament\Concerns;

use App\Models\User;

trait AuthorizesStaff
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessPanel(filament()->getCurrentOrDefaultPanel());
    }

    public static function canAccessAsAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }
}
