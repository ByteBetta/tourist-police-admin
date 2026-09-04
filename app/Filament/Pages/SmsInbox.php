<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AuthorizesStaff;
use App\Repositories\SmsReportRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use UnitEnum;

class SmsInbox extends Page implements HasTable
{
    use AuthorizesStaff;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'SMS inbox';

    protected static ?string $title = 'Emergency SMS inbox';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'sms-inbox';

    public static function canAccess(): bool
    {
        return static::canAccessAsAdmin();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search): array => app(SmsReportRepository::class)->all($search))
            ->columns([
                TextColumn::make('from')->label('Sender'),
                TextColumn::make('body')->limit(80)->wrap(),
                IconColumn::make('converted')
                    ->label('Linked')
                    ->boolean()
                    ->state(fn (array $record): bool => filled($record['reportId'])),
                TextColumn::make('createdAt')
                    ->label('Received')
                    ->formatStateUsing(fn (?string $state): string => $state ? Carbon::parse($state)->diffForHumans() : '—'),
            ])
            ->recordActions([
                Action::make('convert')
                    ->label('Convert to report')
                    ->visible(fn (array $record): bool => blank($record['reportId']))
                    ->requiresConfirmation()
                    ->action(function (array $record): void {
                        $report = app(SmsReportRepository::class)->convertToReport($record['id'], auth()->user());
                        Notification::make()->title('SMS converted to a report.')->success()->send();
                        $this->redirect(ViewReport::getUrl(['recordId' => $report['id']]));
                    }),
                Action::make('openReport')
                    ->label('Open report')
                    ->visible(fn (array $record): bool => filled($record['reportId']))
                    ->url(fn (array $record): string => ViewReport::getUrl(['recordId' => $record['reportId']])),
            ])
            ->paginated([10, 25, 50]);
    }
}
