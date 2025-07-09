<?php
namespace App\Filament\Resources;

use Filament\Resources\Resource;
use App\Models\DemandeDevis;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

class MesDemandesResource extends Resource
{
    protected static ?string $model = DemandeDevis::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Mes Demandes';
    protected static ?string $navigationGroup = 'Agent';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('created_by', auth()->id());
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('agent-service');
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('denomination')
                    ->label('Produit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('prix_total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR'),
                Tables\Columns\BadgeColumn::make('statut')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved_service',
                        'primary' => 'approved_budget',
                        'success' => 'approved_achat',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (DemandeDevis $record) => $record->statut === 'pending'),
            ]);
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
