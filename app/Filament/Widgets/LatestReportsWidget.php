<?php

namespace App\Filament\Widgets;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Filament\Pages\ViewReport;
use App\Repositories\ReportRepository;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;

class LatestReportsWidget extends TableWidget
{
    protected static ?string $heading = 'Newest unassigned reports';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): array {
                $user = auth()->user();

                return collect(app(ReportRepository::class)->all(viewer: $user))
                    ->filter(function (array $report) use ($user): bool {
                        if ($user?->isResponder()) {
                            return in_array($report['status'], [
                                ReportStatus::Pending->value,
                                ReportStatus::Approved->value,
                                ReportStatus::Assigned->value,
                                ReportStatus::Accepted->value,
                                ReportStatus::InProgress->value,
                            ], true);
                        }

                        return empty($report['assignedTo']) && ! in_array($report['status'], [
                            ReportStatus::Resolved->value,
                            ReportStatus::Rejected->value,
                            ReportStatus::Closed->value,
                        ], true);
                    })
                    ->take(8)
                    ->values()
                    ->all();
            })
            ->columns([
                TextColumn::make('title')->limit(40),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ReportType::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => ReportType::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ReportStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => ReportStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('touristName')->label('Tourist'),
                TextColumn::make('createdAt')
                    ->label('Submitted')
                    ->formatStateUsing(fn (?string $state): string => $state ? Carbon::parse($state)->diffForHumans() : '—'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->url(fn (array $record): string => ViewReport::getUrl(['recordId' => $record['id']])),
            ])
            ->paginated(false);
    }
}
