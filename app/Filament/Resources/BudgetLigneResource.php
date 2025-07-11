<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BudgetLigneResource\Pages;
use App\Filament\Resources\BudgetLigneResource\RelationManagers;
use App\Models\BudgetLigne;
use App\Models\Service;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\Summarizers\Sum; // Corrected import
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class BudgetLigneResource extends Resource
{
    protected static ?string $model = BudgetLigne::class;

    protected static ?string $navigationGroup = 'Gestion Budget';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $recordTitleAttribute = 'intitule';

    public static function form(Form $form): Form
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        return $form->schema([
            Section::make('Identification Budget')->schema([
                Select::make('service_id')
                    ->relationship('service', 'nom')
                    ->required()
                    ->searchable()
                    ->preload()
                    // RB can select any service, SD is fixed to their service
                    ->disabled(fn (): bool => $currentUser->hasRole('service-demandeur') && $currentUser->service_id !== null)
                    ->default(fn (): ?int => $currentUser->hasRole('service-demandeur') ? $currentUser->service_id : null),
                DatePicker::make('date_prevue')
                    ->required()
                    ->label('Date d\'achat prévue')
                    ->displayFormat('d/m/Y'),
                TextInput::make('intitule')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ex: Licence Office 365'),
            ])->columns(2),

            Section::make('Détails Techniques')->schema([
                Select::make('nature')
                    ->options([
                        'abonnement' => 'Abonnement annuel',
                        'materiel' => 'Matériel informatique',
                        'infrastructure' => 'Infrastructure',
                        'service' => 'Prestation service'
                    ])
                    ->required()
                    ->native(false),
                TextInput::make('fournisseur_prevu')
                    ->maxLength(255)
                    ->placeholder('Nom du fournisseur'),
                Select::make('base_calcul')
                    ->options([
                        'estimation' => 'Estimation',
                        'prix_ferme' => 'Prix ferme confirmé'
                    ])
                    ->required()
                    ->native(false),
                TextInput::make('quantite')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->step(1),
            ])->columns(2),

            Section::make('Aspects Financiers')->schema([
                TextInput::make('montant_ht_prevu')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('€')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (is_numeric($state)) {
                            $set('montant_ttc_prevu', round((float)$state * 1.20, 2));
                        } else {
                            $set('montant_ttc_prevu', null);
                        }
                    }),
                TextInput::make('montant_ttc_prevu')
                    ->numeric()
                    ->prefix('€')
                    ->disabled()
                    ->dehydrated(), // Ensures it's saved even if disabled
                Select::make('type_depense')
                    ->options([
                        'CAPEX' => 'CAPEX (Investissement)',
                        'OPEX' => 'OPEX (Fonctionnement)'
                    ])
                    ->required()
                    ->native(false),
                Select::make('categorie')
                    ->options([
                        'software' => 'Logiciel/License',
                        'hardware' => 'Matériel informatique',
                        'mobilier' => 'Mobilier/Équipement',
                        'service' => 'Prestation service'
                    ])
                    ->required()
                    ->native(false),
            ])->columns(2),

            Section::make('Validation & Commentaires')
                ->schema([
                    Textarea::make('commentaire_service')
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Commentaire du service demandeur'),
                    Textarea::make('commentaire_budget')
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Commentaire responsable budget')
                        ->visible(fn (): bool => optional($currentUser)->hasRole('responsable-budget')),
                    Select::make('valide_budget')
                        ->options([
                            'non' => 'Non validé',
                            'potentiellement' => 'Potentiellement validé',
                            'oui' => 'Validé définitivement'
                        ])
                        ->default('non')
                        ->native(false)
                        ->visible(fn (): bool => optional($currentUser)->hasRole('responsable-budget')),
                ])
                // This section itself is visible to RB or SD
                ->visible(fn (): bool => optional($currentUser)->hasAnyRole(['responsable-budget', 'service-demandeur']) ?? false)
        ]);
    }

    public static function table(Table $table): Table
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        return $table
            ->columns([
                TextColumn::make('service.nom')
                    ->label('Service')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->visible(fn (): bool => !$currentUser->hasRole('service-demandeur')), // Hide for SD as it's always their service
                TextColumn::make('date_prevue')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Date prévue'),
                TextColumn::make('intitule')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (is_string($state) && strlen($state) > 30) {
                            return $state;
                        }
                        return null;
                    }),
                TextColumn::make('nature')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'abonnement' => 'info',
                        'materiel' => 'success',
                        'infrastructure' => 'warning',
                        'service' => 'primary',
                        default => 'gray'
                    }),
                TextColumn::make('montant_ht_prevu')
                    ->money('EUR')
                    ->sortable()
                    ->summarize(Sum::make()->money('EUR')->label('Total HT Prévu')),
                TextColumn::make('montant_depense_reel_calculated') // Using accessor from model
                    ->money('EUR')
                    ->label('Dépensé Réel HT')
                    ->getStateUsing(fn (BudgetLigne $record): float => $record->montant_depense_reel_calculated)
                    ->color(fn ($record) => $record && $record->montant_depense_reel_calculated > $record->montant_ht_prevu ? 'danger' : 'success'),
                TextColumn::make('budget_restant') // Using method from model
                    ->money('EUR')
                    ->label('Budget Restant HT')
                    ->getStateUsing(fn (BudgetLigne $record): float => $record->calculateBudgetRestant())
                    ->color(fn ($state): string => $state < 0 ? 'danger' : ($state < 100 ? 'warning' : 'success')),

                BadgeColumn::make('valide_budget')
                    ->label('Statut Validation')
                    ->colors([
                        'success' => 'oui',
                        'danger' => 'non',
                        'warning' => 'potentiellement',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'oui',
                        'heroicon-o-x-circle' => 'non',
                        'heroicon-o-clock' => 'potentiellement',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'oui' => 'Validé',
                        'non' => 'Non Validé',
                        'potentiellement' => 'Potentiel',
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'nom')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => optional($currentUser)->hasAnyRole(['responsable-budget', 'service-achat']) ?? false),
                SelectFilter::make('nature')
                    ->options([
                        'abonnement' => 'Abonnement annuel',
                        'materiel' => 'Matériel informatique',
                        'infrastructure' => 'Infrastructure',
                        'service' => 'Prestation service'
                    ]),
                SelectFilter::make('categorie')
                     ->options([
                        'software' => 'Logiciel/License',
                        'hardware' => 'Matériel informatique',
                        'mobilier' => 'Mobilier/Équipement',
                        'service' => 'Prestation service'
                    ]),
                SelectFilter::make('valide_budget')
                    ->label('Statut Validation')
                    ->options([
                        'oui' => 'Validé',
                        'non' => 'Non validé',
                        'potentiellement' => 'Potentiellement'
                    ]),
                Filter::make('depassement')
                    ->label('En dépassement')
                    ->query(fn (Builder $query): Builder => $query->whereRaw(static::getBudgetRestantRawExpression() . ' < 0')),

                Filter::make('budget_epuise')
                    ->label('Budget presque épuisé (>90%)')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('montant_ht_prevu', '>', 0) // Avoid division by zero or meaningless for 0 budget
                        ->whereRaw(static::getMontantDepenseReelRawExpression() . ' >= montant_ht_prevu * 0.9')),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn (BudgetLigne $record): bool => Auth::user()->can('update', $record)),
                Action::make('historique_demandes')
                    ->label('Demandes')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->url(fn (BudgetLigne $record): string => DemandeDevisResource::getUrl('index', ['tableFilters[budget_ligne_id][value]' => $record->id])),
                Action::make('nouvelle_demande')
                    ->label('Nouvelle Demande')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn (BudgetLigne $record): string => DemandeDevisResource::getUrl('create', ['budget_ligne_id' => $record->id, 'service_id' => $record->service_id]))
                    ->visible(fn (BudgetLigne $record): bool =>
                        Auth::user()->can('create', DemandeDevis::class) &&
                        $record->valide_budget === 'oui' &&
                        $record->calculateBudgetRestant() > 0 &&
                        ($currentUser->hasRole('service-demandeur') ? $currentUser->service_id === $record->service_id : true) // SD can only create for their service
                    ),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => Auth::user()->can('delete_any', BudgetLigne::class)),
                BulkAction::make('validate_budget_selection')
                    ->label('Valider budgets sélectionnés')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each(function (BudgetLigne $record) {
                            if (Auth::user()->can('validateBudget', $record)) { // Using policy method
                                $record->update(['valide_budget' => 'oui', 'commentaire_budget' => ($record->commentaire_budget ? $record->commentaire_budget . "\n" : '') . "Validé en masse."]);
                            }
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn (): bool => $currentUser->hasRole('responsable-budget')),
            ])
            ->defaultSort('date_prevue', 'desc')
            ->striped();
    }

    // Helper for raw expressions in summaries/filters
    protected static function getMontantDepenseReelRawExpression(): string
    {
        return '(SELECT SUM(dd.prix_total_ttc) FROM demande_devis dd WHERE dd.budget_ligne_id = budget_lignes.id AND dd.statut = \'delivered\')';
    }

    protected static function getBudgetRestantRawExpression(): string
    {
        return '(budget_lignes.montant_ht_prevu - COALESCE(' . static::getMontantDepenseReelRawExpression() . ', 0))';
    }


    public static function getRelations(): array
    {
        return [
            // RelationManagers\DemandesAssocieesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBudgetLignes::route('/'),
            'create' => Pages\CreateBudgetLigne::route('/create'),
            'edit' => Pages\EditBudgetLigne::route('/{record}/edit'),
            'view' => Pages\ViewBudgetLigne::route('/{record}'),
        ];
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        return route(static::getRouteBaseName(panel: $panel).'.'.$name, $parameters, $isAbsolute);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->hasRole('service-demandeur') && $currentUser->service_id) {
            // This global scope is already on the model, but reinforcing here for clarity or if global scope is removed.
            $query->where('service_id', $currentUser->service_id);
        }
        // For service-achat, they might need to see all budget lines for context when approving demands.
        // No specific filter here for service-achat, policies will handle specific actions.

        return $query->with('service'); // Eager load service for display
    }

    // Optional: Global search attributes
    public static function getGloballySearchableAttributes(): array
    {
        return ['intitule', 'service.nom', 'fournisseur_prevu'];
    }
}
