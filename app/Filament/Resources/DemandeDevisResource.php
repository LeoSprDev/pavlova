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
// use RingleSoft\LaravelProcessApproval\Filament\Actions\ApproveAction;
// use RingleSoft\LaravelProcessApproval\Filament\Actions\RejectAction;
// use RingleSoft\LaravelProcessApproval\Filament\Actions\SubmitAction;
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
                            ->disabled(fn(User $currentUserInClosure, ?DemandeDevis $recordInClosure): bool => // Renamed for clarity
                                $currentUserInClosure->hasRole('agent-service') ||
                                ($recordInClosure && $recordInClosure->statut !== 'pending' && $recordInClosure->statut !== 'rejected')
                            )
                            ->default(fn(User $currentUserInClosure): ?int =>
                                $currentUserInClosure->hasRole('agent-service') ? $currentUserInClosure->service_id : null
                            ),
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
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->downloadable()
                            ->openable()
                            ->directory('devis')
                            ->visibility('private'), // Store in private storage
                        FileUpload::make('documents_complementaires_upload')
                            ->label('Documents Complémentaires (PDF, JPG, PNG)')
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
                            ->visible(fn(User $currentUserInClosure): bool => $currentUserInClosure->hasAnyRole(['responsable-service', 'responsable-budget', 'service-achat'])),
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
                    ->visible(fn(User $currentUserInClosure) => !$currentUserInClosure->hasRole('agent-service')), // Adapté pour agent-service
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
                        'validation-responsable-service' => 'info', // Nouvelle étape, couleur à choisir (info, primary, etc.)
                        'validation-budget' => 'warning',
                        'validation-achat' => 'primary', // Changé 'info' en 'primary' pour distinguer
                        'controle-reception' => 'success', // Étape finale avant 'Terminé'
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
                Filter::make('mes_demandes_agent')
                    ->label('Mes Demandes (Agent)')
                    ->query(fn (Builder $query): Builder => $query->where('user_id', Auth::id())) // Basé sur user_id
                    ->visible(fn(User $currentUserInClosure): bool => $currentUserInClosure->hasRole('agent-service')),
                Filter::make('demandes_mon_service')
                    ->label('Demandes de Mon Service')
                    ->query(fn (Builder $query): Builder => $query->where('service_demandeur_id', Auth::user()->service_id))
                    ->visible(fn(User $currentUserInClosure): bool => $currentUserInClosure->hasRole('responsable-service')),
                Filter::make('a_valider_responsable_service')
                    ->label('À Valider (Resp. Service)')
                    ->query(fn (Builder $query): Builder => $query->where('current_step', 'validation-responsable-service'))
                    ->visible(fn(User $currentUserInClosure): bool => $currentUserInClosure->hasRole('responsable-service')),
                Filter::make('a_valider_budget')
                    ->label('À Valider (Budget)')
                    ->query(fn (Builder $query): Builder => $query->where('current_step', 'validation-budget')) // Étape mise à jour
                    ->visible(fn(User $currentUserInClosure): bool => $currentUserInClosure->hasRole('responsable-budget')),
                Filter::make('a_valider_achat')
                    ->label('À Valider (Achat)')
                    ->query(fn (Builder $query): Builder => $query->where('current_step', 'validation-achat')) // Étape mise à jour
                    ->visible(fn(User $currentUserInClosure): bool => $currentUserInClosure->hasRole('service-achat')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (DemandeDevis $record): bool => Auth::user()->can('update', $record)),
                ActionGroup::make([
                    // --- Actions pour l'étape: validation-responsable-service ---
                    Action::make('approve_service')
                        ->label('Approuver (Resp. Service)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([Textarea::make('approval_comment')->label('Commentaire (optionnel)')])
                        ->action(function(DemandeDevis $record, array $data) {
                            $record->approve(Auth::user(), $data['approval_comment']);
                            $record->statut = 'pending_budget_validation'; // STATUT MIS A JOUR
                            $record->save();
                        })
                        ->visible(fn(DemandeDevis $record): bool => Auth::user()->can('approveValidationResponsableService', $record)),
                    Action::make('reject_service')
                        ->label('Rejeter (Resp. Service)')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([Textarea::make('rejection_comment')->label('Motif du rejet')->required()])
                        ->action(function(DemandeDevis $record, array $data) {
                            $record->reject(Auth::user(), $data['rejection_comment']);
                            $record->statut = 'rejected'; // STATUT MIS A JOUR
                            $record->save();
                        })
                        ->visible(fn(DemandeDevis $record): bool => Auth::user()->can('rejectValidationResponsableService', $record)),

                    // --- Actions pour l'étape: validation-budget ---
                    Action::make('approve_budget')
                        ->label('Approuver (Budget)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([Textarea::make('approval_comment')->label('Commentaire (optionnel)')])
                        ->action(function(DemandeDevis $record, array $data) {
                            $record->approve(Auth::user(), $data['approval_comment']);
                            $record->statut = 'pending_achat_validation'; // STATUT MIS A JOUR
                            $record->date_validation_budget = now();
                            $record->save();
                        })
                        ->visible(fn(DemandeDevis $record): bool => Auth::user()->can('approveValidationBudget', $record)),
                    Action::make('reject_budget')
                        ->label('Rejeter (Budget)')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([Textarea::make('rejection_comment')->label('Motif du rejet')->required()])
                        ->action(function(DemandeDevis $record, array $data) {
                            $record->reject(Auth::user(), $data['rejection_comment']);
                            $record->statut = 'rejected'; // STATUT MIS A JOUR
                            $record->save();
                        })
                        ->visible(fn(DemandeDevis $record): bool => Auth::user()->can('rejectValidationBudget', $record)),

                    // --- Actions pour l'étape: validation-achat ---
                    Action::make('approve_achat')
                        ->label('Approuver (Achat)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([Textarea::make('approval_comment')->label('Commentaire (optionnel)')])
                        ->action(function(DemandeDevis $record, array $data) {
                            $record->approve(Auth::user(), $data['approval_comment']);
                            $record->statut = 'awaiting_delivery'; // STATUT MIS A JOUR
                            $record->date_validation_achat = now();

                            // Créer la Commande
                            \App\Models\Commande::create([
                                'demande_devis_id' => $record->id,
                                'date_commande' => now(),
                                'commanditaire' => Auth::user()->name, // Ou un nom plus générique
                                'statut' => 'en_cours', // Statut initial de la commande
                                'montant_reel' => $record->prix_total_ttc,
                                'fournisseur_contact' => $record->fournisseur_propose,
                                // Les autres champs comme date_livraison_prevue, conditions_paiement etc.
                                // devront être remplis via une interface de gestion des commandes.
                            ]);

                            $record->save();
                        })
                        ->visible(fn(DemandeDevis $record): bool => Auth::user()->can('approveValidationAchat', $record)),
                    Action::make('reject_achat')
                        ->label('Rejeter (Achat)')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([Textarea::make('rejection_comment')->label('Motif du rejet')->required()])
                        ->action(function(DemandeDevis $record, array $data) {
                            $record->reject(Auth::user(), $data['rejection_comment']);
                            $record->statut = 'rejected'; // STATUT MIS A JOUR
                            $record->save();
                        })
                        ->visible(fn(DemandeDevis $record): bool => Auth::user()->can('rejectValidationAchat', $record)),

                    // --- Actions pour l'étape: controle-reception ---
                    Action::make('approve_reception')
                        ->label('Confirmer Réception Conforme')
                        ->icon('heroicon-o-check-badge') // Changed icon
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([Textarea::make('approval_comment')->label('Commentaire de réception (optionnel)')])
                        ->action(function(DemandeDevis $record, array $data) {
                            $record->approve(Auth::user(), $data['approval_comment']);
                            $record->statut = 'delivered';
                            $record->save();

                            // Mettre à jour la Commande et la Livraison associées
                            if ($commande = $record->commande) {
                                $commande->statut = 'livree_validee'; // Ou 'terminee'
                                $commande->save();

                                if ($livraison = $commande->livraison) {
                                    $livraison->statut_reception = 'recue_confirmee'; // Statut personnalisé
                                    $livraison->conforme = true;
                                    $livraison->commentaire_reception = ($livraison->commentaire_reception ? $livraison->commentaire_reception . "\n" : '') . "Réception confirmée par workflow: " . ($data['approval_comment'] ?? 'OK');
                                    $livraison->verifie_par = Auth::id(); // Enregistrer qui a vérifié
                                    $livraison->date_livraison = $livraison->date_livraison ?? now(); // S'assurer qu'une date de livraison est set
                                    $livraison->save();
                                }
                            }
                        })
                        ->visible(fn(DemandeDevis $record): bool => Auth::user()->can('approveControleReception', $record)),
                    Action::make('reject_reception') // Pour signaler un problème à la réception
                        ->label('Signaler Problème Réception')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->form([Textarea::make('rejection_comment')->label('Description du problème')->required()])
                        ->action(function(DemandeDevis $record, array $data) {
                            // Logique à définir pour le signalement de problème
                        })
                        ->visible(false), // Maintenir commenté/invisible pour l'instant

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
            // RelationManagers\ApprobationHistoriqueRelationManager::class, // For laravel-process-approval history
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

        if ($currentUser->hasRole('agent-service')) {
            // L'agent ne voit que les demandes qu'il a créées (nécessite user_id sur DemandeDevis)
            // Assumant que user_id sera ajouté et rempli.
             $query->where('user_id', $currentUser->id);
        } elseif ($currentUser->hasRole('responsable-service')) {
            // Le responsable de service voit toutes les demandes de son service
            $query->where('service_demandeur_id', $currentUser->service_id);
        } elseif ($currentUser->hasRole('service-achat')) {
            // Service Achat voit les demandes à l'étape 'validation-achat' ou celles qu'il a traitées.
            $query->where(function (Builder $q) {
                $q->where('current_step', 'validation-achat') // Nouvelle étape
                  ->orWhereHas('approvalsHistory', function (Builder $approvalQuery) { // Plus robuste que le statut
                      $approvalQuery->where('step', 'validation-achat')
                                    ->where('status', 'approved');
                  })
                  ->orWhereIn('statut', ['delivered']); // Et celles qui sont livrées et qu'ils ont approuvées
            });
        } elseif ($currentUser->hasRole('responsable-budget')) {
            // Le Responsable Budget voit les demandes à l'étape 'validation-budget' ou celles qu'il a traitées,
            // ou toutes si admin-like sur les budgets. Pour l'instant, celles à valider par lui.
            // Les filtres de table permettront de voir plus large.
            // $query->where('current_step', 'validation-budget'); // Trop restrictif pour la vue générale
            // Laisser ouvert pour RB, les filtres affineront.
        }
        // Admin voit tout par défaut.

        return $query->with(['serviceDemandeur', 'budgetLigne', 'creator']); // Ajout de 'creator'
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
