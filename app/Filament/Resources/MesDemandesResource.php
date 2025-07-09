<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DemandeDevisResource;
use App\Models\DemandeDevis;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MesDemandesResource extends Resource
{
    protected static ?string $model = DemandeDevis::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Mes Demandes';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('created_by', auth()->id());
    }

    public static function canAccess(): bool
    {
        return auth()->user() && auth()->user()->hasRole(['agent-service','responsable-service']);
    }

    public static function form(Form $form): Form
    {
        return DemandeDevisResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return DemandeDevisResource::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => MesDemandesResource\Pages\ListMesDemandes::route('/'),
            'create' => MesDemandesResource\Pages\CreateMesDemandes::route('/create'),
            'edit' => MesDemandesResource\Pages\EditMesDemandes::route('/{record}/edit'),
        ];
    }
}
