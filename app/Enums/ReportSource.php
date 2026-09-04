<?php

namespace App\Enums;

enum ReportSource: string
{
    case App = 'app';
    case Sms = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::App => 'Mobile app',
            self::Sms => 'Emergency SMS',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $source): array => [$source->value => $source->label()])
            ->all();
    }
}
