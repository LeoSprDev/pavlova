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
use Illuminate\Database\Eloquent\Model;
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
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DemandesDevisExport;
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
                            ->relationship(
                                name: 'serviceDemandeur',
                                titleAttribute: 'nom',
                                modifyQueryUsing: function (Builder $query) use ($currentUser) {
                                    // Si c'est un agent ou responsable de service, ne montrer que son service
                                    if ($currentUser->hasAnyRole(['agent-service', 'service-demandeur', 'responsable-service']) && $currentUser->service_id) {
                                        $query->where('id', $currentUser->service_id);
                                    }
                                    // Les administrateurs et autres rôles voient tous les services
                                    return $query;
                                }
                            )
                            ->label('Service Demandeur')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn(): bool => $currentUser->hasAnyRole(['service-demandeur', 'agent-service', 'responsable-service']) || ($record && $record->stattr !== 'pending') )
                            ->default(fn(): ?int => $currentUser->hasAnyRole(['service-demandeur', 'agent-service', 'responsable-service']) ? $currentUser->service_id : request()->get('service_id')),
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
                                $prixTotalTTC = $get('prix_total_ttc');
                                if ($budgetLigneId) {
                                    $ligne = BudgetLigne::find($budgetLigneId);
                                    if ($ligne) {
                                        $restant = $ligne->calculateBudgetRestant();
                                        $color = $restant >= 0 ? 'text-green-600' : 'text-red-600';
                                        $warning = '';
                                        
                                        // Avertissement si la demande dépasse le budget restant
                                        if ($prixTotalTTC && $prixTotalTTC > $restant) {
                                            $depassement = $prixTotalTTC - $restant;
                                            $warning = "<div class='mt-2 p-2 bg-orange-100 border border-orange-400 rounded text-orange-800 text-sm'>
                                                <strong>⚠️ AVERTISSEMENT :</strong> Cette demande dépasse le budget de <strong>" . number_format($depassement, 2, ',', ' ') . " €</strong>.
                                                <br>La demande sera créée mais les valideurs seront alertés.
                                            </div>";
                                        }
                                        
                                        return new HtmlString("<span class='font-semibold $color'>" . number_format($restant, 2, ',', ' ') . " €</span>" . $warning);
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
                        TextInput::make('lien_web')
                            ->label('Lien web (optionnel)')
                            ->url()
                            ->placeholder('https://exemple.com/produit')
                            ->helperText('Lien vers le produit, fournisseur ou documentation')
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
                            ->format('Y-m-d')
                            ->displayFormat('d/m/Y')
                            ->default(now()),
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
                            ->visible(fn(): bool => optional($currentUser)->hasAnyRole(['responsable-budget', 'service-achat']) ?? false),
                        // Placeholder for approval actions, actual actions are in the table/view page
                        Placeholder::make('approval_status_info')
                            ->label('Statut Actuel')
                            ->content(fn(?DemandeDevis $record): string => $record ? $record->getCurrentApprovalStepLabel() : 'Nouvelle Demande')
                            ->visible(fn(?DemandeDevis $record) => $record !== null),
                    ])
                    ->visible(fn(?DemandeDevis $record) => $record !== null && $record->statut !== 'pending'), // Only show if not new
            ])->columnSpanFull()
            // Disable wizard steps if the record is no longer pending (for edit view)
            ->disabled(fn(?DemandeDevis $record) => $record && $record->statut !== 'pending' && $record->statut !== 'rejected'),

            Forms\Components\Section::make('Processus Fournisseur')
                ->schema([
                    Forms\Components\DatePicker::make('date_envoi_demande_fournisseur')
                        ->label('Date envoi demande au fournisseur')
                        ->format('Y-m-d')
                        ->displayFormat('d/m/Y'),
                    Forms\Components\FileUpload::make('devis_fournisseur_recu')
                        ->label('Devis reçu du fournisseur (OBLIGATOIRE)')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->directory('devis-fournisseurs')
                        ->required()
                        ->downloadable()
                        ->openable()
                        ->maxSize(10240)
                        ->helperText('Upload du devis final reçu du fournisseur'),
                    Forms\Components\DatePicker::make('date_reception_devis')
                        ->label('Date réception devis')
                        ->format('Y-m-d')
                        ->displayFormat('d/m/Y')
                        ->required(),
                    Forms\Components\TextInput::make('prix_fournisseur_final')
                        ->label('Prix final confirmé fournisseur')
                        ->numeric()
                        ->prefix('€')
                        ->required()
                        ->helperText('Prix final après négociation'),
                    Forms\Components\Toggle::make('devis_fournisseur_valide')
                        ->label('Devis fournisseur validé et commande passée')
                        ->live(),
                    Forms\Components\TextInput::make('numero_commande_fournisseur')
                        ->label('N° commande fournisseur')
                        ->visible(fn (Forms\Get $get) => $get('devis_fournisseur_valide'))
                        ->required(fn (Forms\Get $get) => $get('devis_fournisseur_valide')),
                ])
                ->collapsed()
                ->visible(fn (Forms\Get $get) =>
                    optional(auth()->user())->hasRole('service-achat') &&
                    $get('statut') === 'approved_achat'
                )
        ]);
    }

    public static function table(Table $table): Table
    {
        $currentUser = Auth::user();

        return $table
            ->columns([
                TextColumn::make('denomination')
                    ->label('Produit/Service')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serviceDemandeur.nom')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('prix_total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),
                BadgeColumn::make('statut')
                    ->label('Statut')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'approved_service',
                        'info' => 'approved_budget',
                        'success' => 'approved_achat',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'En attente responsable service',
                        'approved_service' => 'Validé par service',
                        'approved_budget' => 'Validé par budget',
                        'approved_achat' => 'Validé par achat',
                        'rejected' => 'Rejeté',
                        default => $state,
                    }),
                BadgeColumn::make('budget_warning')
                    ->label('Budget')
                    ->getStateUsing(function ($record) {
                        if ($record->budgetLigne) {
                            $restant = $record->budgetLigne->calculateBudgetRestant();
                            $depassement = $record->prix_total_ttc - $restant;
                            if ($depassement > 0) {
                                return 'DÉPASSEMENT: +' . number_format($depassement, 0, ',', ' ') . '€';
                            }
                        }
                        return 'OK';
                    })
                    ->color(fn ($state) => $state === 'OK' ? 'success' : 'warning')
                    ->visible(fn () => auth()->user()->hasAnyRole(['responsable-service', 'responsable-budget', 'service-achat', 'administrateur'])),
                TextColumn::make('lien_web')
                    ->label('Lien')
                    ->formatStateUsing(function ($state) {
                        if ($state) {
                            return new HtmlString('<a href="' . $state . '" target="_blank" class="text-blue-600 hover:text-blue-800">🔗 Voir le lien</a>');
                        }
                        return '-';
                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->options([
                        'pending' => 'En attente responsable service',
                        'approved_service' => 'Validé par service',
                        'approved_budget' => 'Validé par budget',
                        'approved_achat' => 'Validé par achat',
                        'rejected' => 'Rejeté',
                    ]),
                Filter::make('mes_demandes')
                    ->label('Mes Demandes')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('created_by', auth()->id()))
                    ->visible(fn() => $currentUser->hasRole('agent-service')),
                Filter::make('mon_service')
                    ->label('Mon Service')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('service_demandeur_id', auth()->user()->service_id))
                    ->visible(fn() => $currentUser->hasRole('responsable-service')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (DemandeDevis $record): bool =>
                        $record->statut === 'pending' && $record->created_by === auth()->id()),
                Action::make('approve_service')
                    ->label('Valider (Service)')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (DemandeDevis $record) {
                        $record->update(['statut' => 'approved_service']);
                        Notification::make()
                            ->title('Demande validée par le service')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (DemandeDevis $record): bool =>
                        $record->statut === 'pending'
                        && optional(auth()->user())->hasAnyRole(['responsable-service', 'administrateur'])
                        && (optional(auth()->user())->hasRole('administrateur') || optional(auth()->user())->canValidateForService($record->service_demandeur_id))),
                
                Action::make('approve_budget')
                    ->label('Valider Budget')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->action(function (DemandeDevis $record) {
                        $record->update(['statut' => 'approved_budget']);
                        Notification::make()
                            ->title('Demande validée au niveau budget')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (DemandeDevis $record): bool =>
                        $record->statut === 'approved_service'
                        && optional(auth()->user())->hasAnyRole(['responsable-budget', 'administrateur'])),

                Action::make('approve_achat')
                    ->label('Valider Achat')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('info')
                    ->action(function (DemandeDevis $record) {
                        $record->update(['statut' => 'approved_achat']);
                        Notification::make()
                            ->title('Demande validée par le service achat')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (DemandeDevis $record): bool =>
                        $record->statut === 'approved_budget'
                        && optional(auth()->user())->hasAnyRole(['service-achat', 'administrateur'])),

                Action::make('create_order')
                    ->label('Créer Commande')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('primary')
                    ->action(function (DemandeDevis $record) {
                        // Créer la commande
                        $commande = \App\Models\Commande::create([
                            'demande_devis_id' => $record->id,
                            'numero_commande' => 'CMD-' . now()->format('Y') . '-' . str_pad($record->id, 4, '0', STR_PAD_LEFT),
                            'date_commande' => now(),
                            'commanditaire' => auth()->user()->name,
                            'statut' => 'en_cours',
                            'montant_reel' => $record->prix_total_ttc,
                            'fournisseur_contact' => $record->fournisseur_propose,
                            'date_livraison_prevue' => $record->date_besoin,
                        ]);
                        
                        // Mettre à jour le statut de la demande
                        $record->update(['statut' => 'ordered']);
                        
                        Notification::make()
                            ->title('Commande ' . $commande->numero_commande . ' créée')
                            ->body('Vous pouvez la consulter dans le menu Commandes')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (DemandeDevis $record): bool =>
                        $record->statut === 'ready_for_order'
                        && optional(auth()->user())->hasAnyRole(['service-achat', 'administrateur'])),

                Action::make('mark_delivered')
                    ->label('Marquer Livré')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->action(function (DemandeDevis $record) {
                        $record->update(['statut' => 'delivered']);
                        Notification::make()
                            ->title('Demande marquée comme livrée')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (DemandeDevis $record): bool =>
                        in_array($record->statut, ['ordered', 'approved_achat'])
                        && optional(auth()->user())->hasAnyRole(['service-demandeur', 'administrateur'])),

                Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('commentaire_rejet')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (DemandeDevis $record, array $data) {
                        $record->update([
                            'statut' => 'rejected',
                            'commentaire_validation' => $data['commentaire_rejet']
                        ]);
                        Notification::make()
                            ->title('Demande rejetée')
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (DemandeDevis $record): bool =>
                        in_array($record->statut, ['pending', 'approved_service', 'approved_budget'])
                        && optional(auth()->user())->hasAnyRole(['responsable-service', 'responsable-budget', 'service-achat', 'administrateur'])),
            ])
            ->bulkActions([
                BulkAction::make('approveSelected')
                    ->label('Approuver Sélection')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records) {
                        foreach ($records as $demande) {
                            $demande->approve(auth()->user(), 'Approbation groupée');
                        }

                        Notification::make()
                            ->title($records->count() . ' demandes approuvées')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool =>
                        optional(auth()->user())->hasAnyRole(['responsable-service', 'administrateur'])
                    ),

                BulkAction::make('rejectSelected')
                    ->label('Rejeter Sélection')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('comment')
                            ->label('Motif du rejet')
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Support\Collection $records, array $data) {
                        foreach ($records as $demande) {
                            $demande->reject(auth()->user(), $data['comment']);
                        }
                    })
                    ->visible(fn (): bool =>
                        optional(auth()->user())->hasAnyRole(['responsable-service', 'administrateur'])
                    ),

                BulkAction::make('export_excel')
                    ->label('Exporter Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (\Illuminate\Support\Collection $records) {
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\DemandesDevisExport($records),
                            'demandes_' . now()->format('Y-m-d') . '.xlsx'
                        );
                    }),
            ]);
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

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        return route(static::getRouteBaseName(panel: $panel).'.'.$name, $parameters, $isAbsolute);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->hasRole('administrateur') || $currentUser->hasRole('responsable-budget') || $currentUser->hasRole('service-achat')) {
            // Admin, responsable budget et service achat voient tout - pas de filtre
        } elseif (($currentUser->hasRole('service-demandeur') || $currentUser->hasRole('responsable-service') || $currentUser->hasRole('agent-service')) && $currentUser->service_id) {
            $query->where('service_demandeur_id', $currentUser->service_id);
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
