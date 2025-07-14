<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Spatie\Permission\Models\Role;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informations du Rôle')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nom du Rôle')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('guard_name')
                            ->label('Guard')
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(3),

                Section::make('Utilisateurs avec ce Rôle')
                    ->schema([
                        TextEntry::make('users')
                            ->label('Utilisateurs')
                            ->listWithLineBreaks()
                            ->limitList(10)
                            ->expandableLimitedList()
                            ->getStateUsing(function (Role $record): array {
                                return $record->users()->with('service')->get()
                                    ->map(fn ($user) => "{$user->name} ({$user->email}) - Service: {$user->service?->nom}")
                                    ->toArray();
                            }),
                    ])
                    ->collapsible(),

                Section::make('Permissions')
                    ->schema([
                        TextEntry::make('permissions')
                            ->label('Permissions associées')
                            ->listWithLineBreaks()
                            ->getStateUsing(function (Role $record): array {
                                return $record->permissions->pluck('name')->toArray();
                            }),
                    ])
                    ->collapsible(),
            ]);
    }
}