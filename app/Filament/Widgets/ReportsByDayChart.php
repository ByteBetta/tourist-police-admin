<?php

namespace App\Filament\Widgets;

use App\Repositories\ReportRepository;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ReportsByDayChart extends ChartWidget
{
    protected ?string $heading = 'Reports by day';

    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $reports = app(ReportRepository::class)->all(viewer: auth()->user());
        $days = collect(range(6, 0))->mapWithKeys(function (int $ago): array {
            $date = now()->subDays($ago)->toDateString();

            return [$date => 0];
        });

        foreach ($reports as $report) {
            $date = Carbon::parse($report['createdAt'] ?? now())->toDateString();

            if ($days->has($date)) {
                $days[$date] = $days[$date] + 1;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Reports',
                    'data' => $days->values()->all(),
                    'borderColor' => '#0f766e',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $days->keys()->map(fn (string $date): string => Carbon::parse($date)->format('M j'))->all(),
        ];
    }
}
