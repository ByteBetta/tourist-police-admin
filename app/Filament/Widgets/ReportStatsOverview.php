<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\AuthorizesStaff;
use App\Repositories\ReportRepository;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReportStatsOverview extends BaseWidget
{
    use AuthorizesStaff;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = app(ReportRepository::class)->stats(auth()->user());

        return [
            Stat::make('Pending review', (string) $stats['pending'])
                ->description('Waiting for approval')
                ->color('warning'),
            Stat::make('In progress', (string) $stats['in_progress'])
                ->description('Assigned, accepted, or active')
                ->color('info'),
            Stat::make('Resolved today', (string) $stats['resolved_today'])
                ->description('Closed out today')
                ->color('success'),
            Stat::make('SMS vs app', $stats['sms'].' / '.$stats['app'])
                ->description('Emergency SMS / mobile app')
                ->color('primary'),
        ];
    }
}
