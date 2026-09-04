<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

class ListActivities extends ManagesContent
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Activities';

    protected static ?string $title = 'Activities';

    protected static ?string $slug = 'activities';

    protected static ?int $navigationSort = 21;

    protected static bool $isDiscovered = true;

    protected function collection(): string
    {
        return 'activities';
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('name')->required(),
            TextInput::make('location'),
            TextInput::make('duration'),
            Textarea::make('description')->rows(4),
        ];
    }

    protected function tableColumns(): array
    {
        return [
            TextColumn::make('name')->searchable(),
            TextColumn::make('location'),
            TextColumn::make('duration'),
        ];
    }
}
