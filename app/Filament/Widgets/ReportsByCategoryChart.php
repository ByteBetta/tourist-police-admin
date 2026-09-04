<?php

namespace App\Filament\Widgets;

use App\Enums\ReportType;
use App\Repositories\ReportRepository;
use Filament\Widgets\ChartWidget;

class ReportsByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Reports by category';

    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $reports = collect(app(ReportRepository::class)->all(viewer: auth()->user()));

        $labels = [];
        $data = [];

        foreach (ReportType::cases() as $type) {
            $labels[] = $type->label();
            $data[] = $reports->where('type', $type->value)->count();
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => ['#0284c7', '#d97706', '#dc2626'],
                ],
            ],
            'labels' => $labels,
        ];
    }
}
