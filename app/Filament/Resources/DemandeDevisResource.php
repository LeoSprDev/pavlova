<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DemandeDevisResource\Pages;
use App\Filament\Resources\DemandeDevisResource\RelationManagers;
use App\Models\DemandeDevis;
use App\Models\BudgetLigne;
use App\Models\Service;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Filament\Actions\ApproveAction;
use RingleSoft\LaravelProcessApproval\Filament\Actions\RejectAction;
use RingleSoft\LaravelProcessApproval\Filament\Actions\SubmitAction;
use Illuminate\Support\HtmlString;

class DemandeDevisResource extends Resource
{
    protected static ?string $model = DemandeDevis::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Achats & Dépenses';
    protected static ?string $recordTitleAttribute = 'denomination';

    public static function form(Form $form): Form
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $record = $form->getRecord(); // DemandeDevis instance on edit, null on create

        return $form->schema([
            Wizard::make([
                Wizard\Step::make('Information Générale')
                    ->schema([
                        Select::make('service_demandeur_id')
                            ->relationship('serviceDemandeur', 'nom')
                            ->label('Service Demandeur')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn(): bool => $currentUser->hasRole('service-demandeur') || ($record && $record->stattr !== 'pending') )
                            ->default(fn(): ?int => $currentUser->hasRole('service-demandeur') ? $currentUser->service_id : request()->get('service_id')),
                        Select::make('budget_ligne_id')
                            ->label('Ligne Budgétaire d\'Imputation')
                            ->relationship(
                                name: 'budgetLigne',
                                titleAttribute: 'intitule',
                                modifyQueryUsing: fn (Builder $query, Forms\Get $get) =>
                                    $query->where('service_id', $get('service_demandeur_id'))
                                          ->where('valide_budget', 'oui')
                                          // Ideally, also filter by budgetLignes that can accept the demand amount
                            )
                            ->getOptionLabelFromRecordUsing(fn (BudgetLigne $record) => "{$record->intitule} (Restant: ".number_format($record->calculateBudgetRestant(),2,',',' ')." €)")
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->disabled(fn() => $record && $record->statut !== 'pending')
                            ->helperText('Seules les lignes budgétaires validées de votre service avec budget disponible sont listées.'),
                        TextInput::make('denomination')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->label('Dénomination du besoin/produit/service'),
                        Textarea::make('description')
                            ->label('Description détaillée')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('reference_produit')
                            ->label('Référence produit/article (si applicable)')
                            ->maxLength(255),
                        TextInput::make('fournisseur_propose')
                            ->label('Fournisseur Proposé/Suggéré')
                            ->maxLength(255),
                    ])->columns(2),
                Wizard\Step::make('Quantités & Prix')
                    ->schema([
                        TextInput::make('quantite')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $prixUnitaire = $get('prix_unitaire_ht');
                                if (is_numeric($state) && is_numeric($prixUnitaire)) {
                                    $set('prix_total_ttc', round((float)$state * (float)$prixUnitaire * 1.20, 2));
                                }
                            }),
                        TextInput::make('prix_unitaire_ht')
                            ->numeric()
                            ->prefix('€')
                            ->label('Prix Unitaire HT')
                            ->minValue(0)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $quantite = $get('quantite');
                                if (is_numeric($state) && is_numeric($quantite)) {
                                    $set('prix_total_ttc', round((float)$state * (float)$quantite * 1.20, 2));
                                }
                            }),
                        TextInput::make('prix_total_ttc')
                            ->numeric()
                            ->prefix('€')
                            ->label('Prix Total TTC Estimé')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->helperText('Calculé automatiquement (HT x Quantité x 1.20)'),
                        Placeholder::make('budget_restant_info')
                            ->label('Budget Restant sur Ligne Sélectionnée')
                            ->content(function (Forms\Get $get): HtmlString {
                                $budgetLigneId = $get('budget_ligne_id');
                                if ($budgetLigneId) {
                                    $ligne = BudgetLigne::find($budgetLigneId);
                                    if ($ligne) {
                                        $restant = $ligne->calculateBudgetRestant();
                                        $color = $restant >= 0 ? 'text-green-600' : 'text-red-600';
                                        return new HtmlString("<span class='font-semibold $color'>" . number_format($restant, 2, ',', ' ') . " €</span>");
                                    }
                                }
                                return new HtmlString("<span class='text-gray-500'>Sélectionnez une ligne budgétaire.</span>");
                            }),
                    ])->columns(2),
                Wizard\Step::make('Justification & Documents')
                    ->schema([
                        Textarea::make('justification_besoin')
                            ->label('Justification du besoin')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        Select::make('urgence')
                            ->options([
                                'normale' => 'Normale',
                                'urgente' => 'Urgente',
                                'critique' => 'Critique (Bloquant)',
                            ])
                            ->default('normale')
                            ->native(false)
                            ->required(),
                        DatePicker::make('date_besoin')
                            ->label('Date de besoin souhaitée')
                            ->required(),
                        FileUpload::make('devis_fournisseur_upload')
                            ->label('Devis Fournisseur (PDF)')
                            ->collection('devis_fournisseur') // Spatie Media Library collection
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->downloadable()
                            ->openable()
                            ->directory('devis')
                            ->visibility('private'), // Store in private storage
                        FileUpload::make('documents_complementaires_upload')
                            ->label('Documents Complémentaires (PDF, JPG, PNG)')
                            ->collection('documents_complementaires')
                            ->multiple()
                            ->maxFiles(5)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120) // 5MB per file
                            ->downloadable()
                            ->openable()
                            ->directory('docs_demandes')
                            ->visibility('private'),
                    ])->columns(2),
                Wizard\Step::make('Validation (si applicable)')
                    ->schema([
                        Textarea::make('commentaire_validation')
                            ->label('Commentaire de validation/rejet')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn(): bool => $currentUser->hasAnyRole(['responsable-budget', 'service-achat'])),
                        // Placeholder for approval actions, actual actions are in the table/view page
                        Placeholder::make('approval_status_info')
                            ->label('Statut Actuel')
                            ->content(fn(?DemandeDevis $record): string => $record ? $record->getCurrentApprovalStepLabel() : 'Nouvelle Demande')
                            ->visible(fn(?DemandeDevis $record) => $record !== null),
                    ])
                    ->visible(fn(?DemandeDevis $record) => $record !== null && $record->statut !== 'pending'), // Only show if not new
            ])->columnSpanFull()
            // Disable wizard steps if the record is no longer pending (for edit view)
            ->disabled(fn(?DemandeDevis $record) => $record && $record->statut !== 'pending' && $record->statut !== 'rejected')
        ]);
    }

    public static function table(Table $table): Table
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        return $table
            ->columns([
                TextColumn::make('denomination')
                    ->searchable()
                    ->sortable()
                    ->limit(35)
                    ->tooltip(fn (DemandeDevis $record) => $record->denomination),
                TextColumn::make('serviceDemandeur.nom')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => !$currentUser->hasRole('service-demandeur')),
                TextColumn::make('budgetLigne.intitule')
                    ->label('Ligne Budgétaire')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->tooltip(fn (DemandeDevis $record) => $record->budgetLigne?->intitule),
                TextColumn::make('prix_total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('date_besoin')
                    ->date('d/m/Y')
                    ->sortable(),
                BadgeColumn::make('statut')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved_budget',
                        'primary' => 'approved_achat',
                        'success' => 'delivered',
                        'danger' => 'rejected',
                        'gray' => 'cancelled',
                    ])
                    ->sortable(),
                TextColumn::make('current_step_label') // Using accessor from model
                    ->label('Étape Actuelle')
                    ->badge()
                    ->getStateUsing(fn (DemandeDevis $record) => $record->getCurrentApprovalStepLabel())
                    ->color(fn (DemandeDevis $record): string => match ($record->getCurrentApprovalStepKey()) {
                        'responsable-budget' => 'warning',
                        'service-achat' => 'info',
                        'reception-livraison' => 'primary',
                        default => ($record->isFullyApproved() ? 'success' : ($record->isRejected() ? 'danger' : 'gray')),
                    }),
            ])
            ->filters([
                SelectFilter::make('service_demandeur_id')
                    ->label('Service Demandeur')
                    ->relationship('serviceDemandeur', 'nom')
                    ->searchable()
                    ->preload()
                    ->visible(fn() => $currentUser->hasAnyRole(['responsable-budget', 'service-achat'])),
                SelectFilter::make('budget_ligne_id')
                    ->label('Ligne Budgétaire')
                    ->relationship('budgetLigne', 'intitule')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('statut')
                    ->options(fn() => collect(DemandeDevis::select('statut')->distinct()->pluck('statut','statut'))->map(fn($s) => ucfirst(str_replace('_',' ',$s))) ),
                Filter::make('mes_demandes')
                    ->label('Mes Demandes (Service)')
                    ->query(fn (Builder $query): Builder => $query->where('service_demandeur_id', $currentUser->service_id))
                    ->visible(fn() => $currentUser->hasRole('service-demandeur')),
                Filter::make('a_valider_budget')
                    ->label('À Valider (Budget)')
                    ->query(fn (Builder $query): Builder => $query->where('current_step', 'responsable-budget')->where('statut', 'pending'))
                    ->visible(fn() => $currentUser->hasRole('responsable-budget')),
                Filter::make('a_valider_achat')
                    ->label('À Valider (Achat)')
                    ->query(fn (Builder $query): Builder => $query->where('current_step', 'service-achat')->where('statut', 'approved_budget'))
                    ->visible(fn() => $currentUser->hasRole('service-achat')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (DemandeDevis $record) => $record->statut === 'pending' && $currentUser->id === $record->user_id), // Example, policy should handle this
                ActionGroup::make([
                    SubmitAction::make()->visible(fn(DemandeDevis $record) => $record->canBeSubmitted()), // From laravel-process-approval
                    ApproveAction::make()->visible(fn(DemandeDevis $record) => $record->canBeApprovedBy(Auth::user())), // From laravel-process-approval
                    RejectAction::make()->visible(fn(DemandeDevis $record) => $record->canBeRejectedBy(Auth::user())),   // From laravel-process-approval
                    // Custom action to mark as 'delivered' if needed outside of workflow, or trigger reception step
                    Action::make('mark_delivered')
                        ->label('Confirmer Livraison (Manuelle)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function(DemandeDevis $record){
                            // This should ideally trigger the 'reception-livraison' step
                            // For now, direct status update if permitted
                            if ($record->statut === 'approved_achat' && $record->commande()->exists()) {
                                // $record->statut = 'delivered'; // This is too simple, workflow should handle it.
                                // $record->save();
                                // Attempt to trigger the next step if it's reception
                                if ($record->getCurrentApprovalStepKey() === 'reception-livraison') {
                                     // $record->approve(Auth::user(), "Livraison confirmée manuellement.");
                                     // Or, if 'on_delivery_upload' is the trigger, this action might simulate that.
                                }
                                // This manual action needs careful consideration with the workflow.
                            }
                        })
                        ->visible(fn(DemandeDevis $record) => $record->statut === 'approved_achat' && $currentUser->hasRole('service-demandeur') && $currentUser->service_id === $record->service_demandeur_id),
                    DeleteAction::make()->visible(fn(DemandeDevis $record) => Auth::user()->can('delete', $record)),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn() => Auth::user()->can('delete_any', DemandeDevis::class)),
                // Add bulk approval/rejection if needed, using similar logic to BudgetLigneResource
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CommandeRelationManager::class,
            RelationManagers\ApprobationHistoriqueRelationManager::class, // For laravel-process-approval history
            // RelationManagers\MediaRelationManager::class, // If you want to manage media here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDemandeDevis::route('/'),
            'create' => Pages\CreateDemandeDevis::route('/create'),
            'edit' => Pages\EditDemandeDevis::route('/{record}/edit'),
            'view' => Pages\ViewDemandeDevis::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->hasRole('service-demandeur') && $currentUser->service_id) {
            $query->where('service_demandeur_id', $currentUser->service_id);
        } elseif ($currentUser->hasRole('service-achat')) {
            // Service Achat should see demands that are at 'service-achat' step or beyond, or assigned to them.
            // The table filters will handle most of this.
            // $query->whereIn('statut', ['approved_budget', 'approved_achat', 'delivered', 'rejected', 'cancelled']);
            // Or based on current_step if that's reliably updated by the approval package
            $query->where(function (Builder $q) {
                $q->where('current_step', 'service-achat')
                  ->orWhereIn('statut', ['approved_achat', 'delivered']); // Also show those they've processed
            });
        }
        // Responsable Budget sees all, filtered by table filters.
        // Admin sees all.

        return $query->with(['serviceDemandeur', 'budgetLigne']);
    }

     public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['serviceDemandeur', 'budgetLigne']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['denomination', 'serviceDemandeur.nom', 'budgetLigne.intitule', 'reference_produit'];
    }
}
