<?php

namespace App\Filament\Pages;

use App\Enums\ReportSource;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Filament\Concerns\AuthorizesStaff;
use App\Repositories\ReportRepository;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use UnitEnum;

class ListReports extends Page implements HasTable
{
    use AuthorizesStaff;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Assistance reports';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'reports';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?string $search, ?array $filters): array {
                return app(ReportRepository::class)->all([
                    'search' => $search,
                    'status' => data_get($filters, 'status.value'),
                    'type' => data_get($filters, 'type.value'),
                    'source' => data_get($filters, 'source.value'),
                ], auth()->user());
            })
            ->columns([
                TextColumn::make('title')->limit(40)->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ReportType::tryFrom((string) $state)?->label() ?? (string) $state)
                    ->color(fn (?string $state): string => ReportType::tryFrom((string) $state)?->color() ?? 'gray'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ReportStatus::tryFrom((string) $state)?->label() ?? (string) $state)
                    ->color(fn (?string $state): string => ReportStatus::tryFrom((string) $state)?->color() ?? 'gray'),
                TextColumn::make('source')
                    ->formatStateUsing(fn (?string $state): string => ReportSource::tryFrom((string) $state)?->label() ?? (string) $state),
                TextColumn::make('touristName')->label('Tourist'),
                TextColumn::make('assignedToName')->label('Responder')->placeholder('Unassigned'),
                TextColumn::make('createdAt')
                    ->label('Submitted')
                    ->formatStateUsing(fn (?string $state): string => $state ? Carbon::parse($state)->timezone(config('app.timezone'))->format('M j, Y g:i A') : '—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ReportStatus::options()),
                SelectFilter::make('type')->options(ReportType::options()),
                SelectFilter::make('source')->options(ReportSource::options()),
            ])
            ->recordUrl(fn (array $record): string => ViewReport::getUrl(['recordId' => $record['id']]))
            ->recordActions([
                Action::make('view')
                    ->url(fn (array $record): string => ViewReport::getUrl(['recordId' => $record['id']])),
            ])
            ->defaultSort('createdAt', 'desc')
            ->paginated([10, 25, 50]);
    }
}
