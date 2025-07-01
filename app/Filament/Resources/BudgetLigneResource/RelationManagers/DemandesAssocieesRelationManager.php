<?php

namespace App\Filament\Resources\BudgetLigneResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\DemandeDevisResource; // For linking actions

class DemandesAssocieesRelationManager extends RelationManager
{
    protected static string $relationship = 'demandesAssociees';

    protected static ?string $recordTitleAttribute = 'denomination'; // or another suitable attribute from DemandeDevis

    public function form(Form $form): Form
    {
        // This form is typically for creating/editing related records directly from the manager.
        // For DemandeDevis, it's complex, so we might redirect to the full DemandeDevisResource form.
        // Or provide a simplified form if inline creation is desired.
        // For now, let's assume we view only or link to full resource.
        return $form
            ->schema([
                // Forms\Components\TextInput::make('denomination')
                //     ->required()
                //     ->maxLength(255),
                // ... other fields if inline editing is enabled
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('denomination')
                    ->label('Dénomination')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved_budget' => 'info',
                        'approved_achat' => 'primary',
                        'delivered' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('prix_total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_besoin')
                    ->label('Date de Besoin')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('urgence')
                    ->label('Urgence')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match($state) {
                        'normale' => 'success',
                        'urgente' => 'warning',
                        'critique' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Action to create a new DemandeDevis linked to this BudgetLigne
                Tables\Actions\Action::make('create_demande_devis')
                    ->label('Nouvelle Demande Associée')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => DemandeDevisResource::getUrl('create', [
                        'budget_ligne_id' => $this->getOwnerRecord()->id,
                        'service_id' => $this->getOwnerRecord()->service_id, // Pre-fill service from budget line
                        ]))
                    ->visible(fn(): bool => $this->getOwnerRecord()->valide_budget === 'oui' && $this->getOwnerRecord()->calculateBudgetRestant() > 0 && auth()->user()->can('create', \App\Models\DemandeDevis::class)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record): string => DemandeDevisResource::getUrl('view', ['record' => $record])),
                Tables\Actions\EditAction::make()
                    ->url(fn ($record): string => DemandeDevisResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn ($record): bool => auth()->user()->can('update', $record)),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
