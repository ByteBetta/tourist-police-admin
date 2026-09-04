<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AuthorizesStaff;
use App\Repositories\ContentRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

abstract class ManagesContent extends Page implements HasTable
{
    use AuthorizesStaff;
    use InteractsWithTable;

    protected static bool $isDiscovered = false;

    protected static string|UnitEnum|null $navigationGroup = 'Offline library';

    public static function canAccess(): bool
    {
        return static::canAccessAsAdmin();
    }

    abstract protected function collection(): string;

    /**
     * @return array<int, mixed>
     */
    abstract protected function formSchema(): array;

    /**
     * @return array<int, TextColumn>
     */
    abstract protected function tableColumns(): array;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Add')
                ->schema($this->formSchema())
                ->action(function (array $data): void {
                    app(ContentRepository::class)->save($this->collection(), $data);
                    Notification::make()->title('Item saved.')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search): array => app(ContentRepository::class)->all($this->collection(), $search))
            ->columns($this->tableColumns())
            ->recordActions([
                Action::make('edit')
                    ->fillForm(fn (array $record): array => $record)
                    ->schema($this->formSchema())
                    ->action(function (array $data, array $record): void {
                        app(ContentRepository::class)->save($this->collection(), array_merge($record, $data), $record['id']);
                        Notification::make()->title('Item updated.')->success()->send();
                    }),
                Action::make('delete')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (array $record): void {
                        app(ContentRepository::class)->delete($this->collection(), $record['id']);
                        Notification::make()->title('Item deleted.')->success()->send();
                    }),
            ])
            ->paginated([10, 25, 50]);
    }
}
