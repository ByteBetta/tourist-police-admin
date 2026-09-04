<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

class ListAttractions extends ManagesContent
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Attractions';

    protected static ?string $title = 'Tourist attractions';

    protected static ?string $slug = 'attractions';

    protected static ?int $navigationSort = 20;

    protected static bool $isDiscovered = true;

    protected function collection(): string
    {
        return 'attractions';
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('name')->required(),
            TextInput::make('address'),
            TextInput::make('hours')->label('Hours'),
            Textarea::make('description')->rows(4),
        ];
    }

    protected function tableColumns(): array
    {
        return [
            TextColumn::make('name')->searchable(),
            TextColumn::make('address')->limit(40),
            TextColumn::make('hours'),
        ];
    }
}
