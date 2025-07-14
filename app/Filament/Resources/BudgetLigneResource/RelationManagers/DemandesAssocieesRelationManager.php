<?php

namespace App\Filament\Resources\BudgetLigneResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DemandesAssocieesRelationManager extends RelationManager
{
    protected static string $relationship = 'demandesAssociees';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('denomination')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('denomination')
            ->columns([
                Tables\Columns\TextColumn::make('denomination')
                    ->label('Produit/Service')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                    
                Tables\Columns\TextColumn::make('serviceDemandeur.nom')
                    ->label('Service Demandeur')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('prix_total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('statut')
                    ->label('Statut')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'approved_service',
                        'info' => 'approved_budget', 
                        'success' => ['approved_achat', 'delivered'],
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'En attente',
                        'approved_service' => 'Validé service',
                        'approved_budget' => 'Validé budget',
                        'approved_achat' => 'Validé achat',
                        'delivered' => 'Livré',
                        'rejected' => 'Rejeté',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('fournisseur_propose')
                    ->label('Fournisseur')
                    ->limit(20),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->options([
                        'pending' => 'En attente',
                        'approved_service' => 'Validé service',
                        'approved_budget' => 'Validé budget',
                        'approved_achat' => 'Validé achat',
                        'delivered' => 'Livré',
                        'rejected' => 'Rejeté',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_demande')
                    ->label('Nouvelle Demande')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => route('filament.admin.resources.demande-devis.create', [
                        'budget_ligne_id' => $this->ownerRecord->id
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record): string => route('filament.admin.resources.demande-devis.view', $record))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\Action::make('edit')
                    ->label('Modifier')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record): string => route('filament.admin.resources.demande-devis.edit', $record))
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => $record->statut === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\Action::make('export')
                        ->label('Exporter sélection')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function ($records) {
                            // Export logic here
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
