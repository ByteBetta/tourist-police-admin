<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Rejected = 'rejected';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Assigned => 'Assigned',
            self::Accepted => 'Accepted',
            self::InProgress => 'In progress',
            self::Resolved => 'Resolved',
            self::Rejected => 'Rejected',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Assigned => 'primary',
            self::Accepted => 'success',
            self::InProgress => 'info',
            self::Resolved => 'success',
            self::Rejected => 'danger',
            self::Closed => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
