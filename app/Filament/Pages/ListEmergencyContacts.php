<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

class ListEmergencyContacts extends ManagesContent
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $navigationLabel = 'Emergency contacts';

    protected static ?string $title = 'Emergency contacts';

    protected static ?string $slug = 'emergency-contacts';

    protected static ?int $navigationSort = 23;

    protected static bool $isDiscovered = true;

    protected function collection(): string
    {
        return 'emergency_contacts';
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('name')->required(),
            TextInput::make('phone')->tel()->required(),
            TextInput::make('agency'),
            Textarea::make('notes')->rows(3),
        ];
    }

    protected function tableColumns(): array
    {
        return [
            TextColumn::make('name')->searchable(),
            TextColumn::make('agency'),
            TextColumn::make('phone'),
        ];
    }
}
