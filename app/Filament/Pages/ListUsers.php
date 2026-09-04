<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Concerns\AuthorizesStaff;
use App\Repositories\UserRepository;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ListUsers extends Page implements HasTable
{
    use AuthorizesStaff;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $title = 'User accounts';

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'users';

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

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('createStaff')
                ->label('Add staff account')
                ->schema($this->staffForm())
                ->action(function (array $data): void {
                    app(UserRepository::class)->createStaff($data);
                    Notification::make()->title('Staff account created.')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?string $search, ?array $filters): array {
                return app(UserRepository::class)->all(
                    data_get($filters, 'role.value'),
                    $search,
                );
            })
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email'),
                TextColumn::make('phone')->placeholder('—'),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => UserRole::tryFrom((string) $state)?->label() ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        UserRole::Admin->value => 'danger',
                        UserRole::Responder->value => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('disabled')->boolean()->label('Disabled'),
            ])
            ->filters([
                SelectFilter::make('role')->options([
                    UserRole::Admin->value => 'Administrator',
                    UserRole::Responder->value => 'Responder',
                    UserRole::Tourist->value => 'Tourist',
                ]),
            ])
            ->recordActions([
                Action::make('edit')
                    ->visible(fn (array $record): bool => in_array($record['role'], [UserRole::Admin->value, UserRole::Responder->value], true))
                    ->fillForm(fn (array $record): array => [
                        'name' => $record['name'],
                        'phone' => $record['phone'],
                        'role' => $record['role'],
                        'disabled' => $record['disabled'],
                    ])
                    ->schema([
                        TextInput::make('name')->required(),
                        TextInput::make('phone')->tel(),
                        Select::make('role')->options(UserRole::staffOptions())->required(),
                        Toggle::make('disabled')->label('Disable account'),
                    ])
                    ->action(function (array $data, array $record): void {
                        app(UserRepository::class)->update($record['id'], $data);
                        Notification::make()->title('Account updated.')->success()->send();
                    }),
                Action::make('toggleDisabled')
                    ->label(fn (array $record): string => $record['disabled'] ? 'Enable' : 'Disable')
                    ->color(fn (array $record): string => $record['disabled'] ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->action(function (array $record): void {
                        app(UserRepository::class)->update($record['id'], ['disabled' => ! $record['disabled']]);
                        Notification::make()->title('Account status updated.')->success()->send();
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

    /**
     * @return array<int, TextInput|Select>
     */
    protected function staffForm(): array
    {
        return [
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(),
            TextInput::make('phone')->tel(),
            Select::make('role')->options(UserRole::staffOptions())->required()->default(UserRole::Responder->value),
            TextInput::make('password')->password()->required()->minLength(8)->revealable(),
        ];
    }
}
