<?php

namespace App\Filament\Pages;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Filament\Concerns\AuthorizesStaff;
use App\Models\User;
use App\Repositories\ReportRepository;
use App\Repositories\UserRepository;
use App\Services\ReportWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as ViewComponent;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class ViewReport extends Page
{
    use AuthorizesStaff;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'reports/{recordId}';

    protected static ?string $title = 'Report details';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public string $recordId = '';

    /**
     * @var array<string, mixed>
     */
    public array $report = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $updates = [];

    public function mount(string $recordId): void
    {
        $this->recordId = $recordId;
        $this->loadReport();
    }

    public function getTitle(): string
    {
        return $this->report['title'] ?? 'Report details';
    }

    public function getBreadcrumbs(): array
    {
        return [
            ListReports::getUrl() => 'Reports',
            $this->getTitle(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Report')
                ->columns(3)
                ->schema([
                    TextEntry::make('status')
                        ->state($this->report['status'] ?? null)
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => ReportStatus::tryFrom((string) $state)?->label() ?? (string) $state)
                        ->color(fn (?string $state): string => ReportStatus::tryFrom((string) $state)?->color() ?? 'gray'),
                    TextEntry::make('type')
                        ->state($this->report['type'] ?? null)
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => ReportType::tryFrom((string) $state)?->label() ?? (string) $state),
                    TextEntry::make('source')->state($this->report['source'] ?? null),
                    TextEntry::make('touristName')->label('Tourist')->state($this->report['touristName'] ?? null),
                    TextEntry::make('touristPhone')->label('Phone')->state($this->report['touristPhone'] ?? '—'),
                    TextEntry::make('assignedToName')->label('Responder')->state($this->report['assignedToName'] ?? 'Unassigned'),
                    TextEntry::make('description')->state($this->report['description'] ?? null)->columnSpanFull(),
                    TextEntry::make('createdAt')
                        ->label('Submitted')
                        ->state($this->formatTimestamp($this->report['createdAt'] ?? null)),
                    TextEntry::make('updatedAt')
                        ->label('Last update')
                        ->state($this->formatTimestamp($this->report['updatedAt'] ?? null)),
                ]),
            Section::make('Photos')
                ->visible(fn (): bool => filled($this->report['photos'] ?? []))
                ->schema([
                    RepeatableEntry::make('photos')
                        ->state($this->photoEntries())
                        ->schema([
                            ImageEntry::make('url')->hiddenLabel()->imageHeight(180),
                        ])
                        ->contained(false)
                        ->grid(3),
                ]),
            ViewComponent::make('filament.reports.map')
                ->visible(fn (): bool => filled(data_get($this->report, 'location.lat')))
                ->viewData([
                    'lat' => data_get($this->report, 'location.lat'),
                    'lng' => data_get($this->report, 'location.lng'),
                    'address' => data_get($this->report, 'location.address'),
                    'mapsKey' => config('services.firebase.maps_key'),
                ]),
            Section::make('Action timeline')
                ->schema([
                    RepeatableEntry::make('updates')
                        ->state($this->updates)
                        ->schema([
                            TextEntry::make('createdAt')
                                ->hiddenLabel()
                                ->formatStateUsing(fn (?string $state): string => $this->formatTimestamp($state)),
                            TextEntry::make('actorName')->label('By')->placeholder('System'),
                            TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(fn (?string $state): string => ReportStatus::tryFrom((string) $state)?->label() ?? (string) $state),
                            TextEntry::make('note')->label('Action taken')->columnSpanFull(),
                        ])
                        ->contained(false),
                ]),
        ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        return [
            Action::make('approve')
                ->visible(fn (): bool => $user->isAdmin() && ($this->report['status'] ?? null) === ReportStatus::Pending->value)
                ->color('success')
                ->requiresConfirmation()
                ->action(function () use ($user): void {
                    app(ReportWorkflowService::class)->approve($this->recordId, $user);
                    $this->refreshAfterAction('Report approved.');
                }),
            Action::make('reject')
                ->visible(fn (): bool => $user->isAdmin() && ($this->report['status'] ?? null) === ReportStatus::Pending->value)
                ->color('danger')
                ->schema([
                    Textarea::make('note')->label('Reason')->required()->rows(3),
                ])
                ->action(function (array $data) use ($user): void {
                    app(ReportWorkflowService::class)->reject($this->recordId, $user, $data['note']);
                    $this->refreshAfterAction('Report rejected.');
                }),
            Action::make('assign')
                ->visible(fn (): bool => $user->isAdmin() && in_array($this->report['status'] ?? null, [
                    ReportStatus::Approved->value,
                    ReportStatus::Assigned->value,
                    ReportStatus::Pending->value,
                ], true))
                ->schema([
                    Select::make('responderId')
                        ->label('Responder')
                        ->options(fn (): array => collect(app(UserRepository::class)->responders())->mapWithKeys(
                            fn (array $responder): array => [$responder['id'] => $responder['name'].' ('.$responder['email'].')'],
                        )->all())
                        ->required()
                        ->searchable(),
                    Textarea::make('note')->rows(2),
                ])
                ->action(function (array $data) use ($user): void {
                    app(ReportWorkflowService::class)->assign($this->recordId, $data['responderId'], $user, $data['note'] ?? null);
                    $this->refreshAfterAction('Responder assigned.');
                }),
            Action::make('accept')
                ->visible(fn (): bool => $user->isResponder() && in_array($this->report['status'] ?? null, [
                    ReportStatus::Approved->value,
                    ReportStatus::Assigned->value,
                    ReportStatus::Pending->value,
                ], true))
                ->color('success')
                ->requiresConfirmation()
                ->action(function () use ($user): void {
                    app(ReportWorkflowService::class)->accept($this->recordId, $user);
                    $this->refreshAfterAction('Case accepted.');
                }),
            Action::make('updateStatus')
                ->label('Update status')
                ->visible(fn (): bool => filled($this->report['assignedTo']) && $this->report['assignedTo'] === $user->firebaseUid()
                    || $user->isAdmin())
                ->schema([
                    Select::make('status')
                        ->options(ReportStatus::options())
                        ->required()
                        ->default($this->report['status'] ?? ReportStatus::InProgress->value),
                    Textarea::make('note')->label('Action taken')->rows(3)->required(),
                ])
                ->action(function (array $data) use ($user): void {
                    app(ReportWorkflowService::class)->updateStatus(
                        $this->recordId,
                        ReportStatus::from($data['status']),
                        $user,
                        $data['note'],
                    );
                    $this->refreshAfterAction('Status updated.');
                }),
        ];
    }

    protected function loadReport(): void
    {
        $report = app(ReportRepository::class)->find($this->recordId);
        abort_unless($report, 404);

        $this->report = $report;
        $this->updates = app(ReportRepository::class)->updates($this->recordId);
    }

    protected function refreshAfterAction(string $message): void
    {
        $this->loadReport();

        Notification::make()->title($message)->success()->send();
    }

    /**
     * @return list<array{url: string}>
     */
    protected function photoEntries(): array
    {
        return collect($this->report['photos'] ?? [])
            ->map(fn (string $url): array => ['url' => $url])
            ->values()
            ->all();
    }

    protected function formatTimestamp(?string $value): string
    {
        return $value ? Carbon::parse($value)->timezone(config('app.timezone'))->format('M j, Y g:i A') : '—';
    }
}
