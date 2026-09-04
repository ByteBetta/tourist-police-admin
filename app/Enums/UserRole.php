<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Responder = 'responder';
    case Tourist = 'tourist';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Responder => 'Responder',
            self::Tourist => 'Tourist',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function staffOptions(): array
    {
        return [
            self::Admin->value => self::Admin->label(),
            self::Responder->value => self::Responder->label(),
        ];
    }
}
