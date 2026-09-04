<?php

namespace App\Enums;

enum ReportType: string
{
    case Assistance = 'assistance';
    case Concern = 'concern';
    case Emergency = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::Assistance => 'Assistance request',
            self::Concern => 'Concern',
            self::Emergency => 'Emergency',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Assistance => 'info',
            self::Concern => 'warning',
            self::Emergency => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
