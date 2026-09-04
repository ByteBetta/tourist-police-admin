<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

class ListEvents extends ManagesContent
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Events';

    protected static ?string $title = 'City events';

    protected static ?string $slug = 'events';

    protected static ?int $navigationSort = 22;

    protected static bool $isDiscovered = true;

    protected function collection(): string
    {
        return 'events';
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('name')->required(),
            TextInput::make('venue'),
            DateTimePicker::make('startsAt')->label('Starts'),
            DateTimePicker::make('endsAt')->label('Ends'),
            Textarea::make('description')->rows(4),
        ];
    }

    protected function tableColumns(): array
    {
        return [
            TextColumn::make('name')->searchable(),
            TextColumn::make('venue'),
            TextColumn::make('startsAt')->label('Starts'),
        ];
    }
}
