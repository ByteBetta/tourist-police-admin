<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LatestReportsWidget;
use App\Filament\Widgets\ReportsByCategoryChart;
use App\Filament\Widgets\ReportsByDayChart;
use App\Filament\Widgets\ReportStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = -2;

    public function getTitle(): string
    {
        $user = auth()->user();

        return $user?->isResponder()
            ? 'My caseload'
            : 'Tourist Police operations';
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            ReportStatsOverview::class,
            ReportsByDayChart::class,
            ReportsByCategoryChart::class,
            LatestReportsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
