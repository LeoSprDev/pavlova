<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CommandeResource\Pages;
use App\Models\Commande;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CommandeResource extends Resource
{
    protected static ?string $model = Commande::class;
    protected static ?string $navigationGroup = 'Achats & Dépenses';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations Commande')->schema([
                Forms\Components\Select::make('demande_devis_id')
                    ->relationship('demandeDevis', 'denomination')
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('numero_commande')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\DatePicker::make('date_commande')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('commanditaire')
                    ->required()
                    ->default(fn() => auth()->user()->name),
                Forms\Components\Select::make('statut')
                    ->options([
                        'en_cours' => 'En cours',
                        'livree_partiellement' => 'Livrée partiellement',
                        'livree' => 'Livrée',
                        'annulee' => 'Annulée'
                    ])
                    ->default('en_cours')
                    ->required(),
                Forms\Components\DatePicker::make('date_livraison_prevue')
                    ->required()
            ]),
            Forms\Components\Section::make('Détails Fournisseur')->schema([
                Forms\Components\TextInput::make('fournisseur_contact')
                    ->maxLength(255),
                Forms\Components\TextInput::make('fournisseur_email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('conditions_paiement')
                    ->maxLength(255)
                    ->placeholder('Ex: 30 jours fin de mois'),
                Forms\Components\TextInput::make('delai_livraison')
                    ->maxLength(255)
                    ->placeholder('Ex: 15 jours ouvrés'),
                Forms\Components\TextInput::make('montant_reel')
                    ->numeric()
                    ->prefix('€')
                    ->required()
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_commande')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('demandeDevis.denomination')
                    ->label('Produit/Service')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('demandeDevis.serviceDemandeur.nom')
                    ->label('Service')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_commande')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_livraison_prevue')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn($record) => $record->isEnRetard() ? 'danger' : null),
                Tables\Columns\BadgeColumn::make('statut')
                    ->colors([
                        'warning' => 'en_cours',
                        'info' => 'livree_partiellement',
                        'success' => 'livree',
                        'danger' => 'annulee'
                    ]),
                Tables\Columns\TextColumn::make('montant_reel')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nb_relances')
                    ->label('Relances')
                    ->color(fn($state) => $state > 2 ? 'danger' : ($state > 0 ? 'warning' : 'success'))
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut'),
                Tables\Filters\Filter::make('en_retard')
                    ->query(fn($query) => $query->where('date_livraison_prevue', '<', now())
                        ->whereDoesntHave('livraison'))
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('relancer')
                    ->icon('heroicon-o-bell')
                    ->color('warning')
                    ->action(function($record) {
                        \App\Jobs\RelanceFournisseurJob::dispatch($record);
                        \Filament\Notifications\Notification::make()
                            ->title('Relance programmée')
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => $record->isEnRetard())
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommandes::route('/'),
            'create' => Pages\CreateCommande::route('/create'),
            'view' => Pages\ViewCommande::route('/{record}'),
            'edit' => Pages\EditCommande::route('/{record}/edit'),
        ];
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        return route(static::getRouteBaseName(panel: $panel).'.'.$name, $parameters, $isAbsolute);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $currentUser = auth()->user();

        if ($currentUser && !$currentUser->hasRole('administrateur')) {
            if (($currentUser->hasRole('service-demandeur') || $currentUser->hasRole('responsable-service')) && $currentUser->service_id) {
                // Filter to show only commands from user's service
                $query->whereHas('demandeDevis', function ($q) use ($currentUser) {
                    $q->where('service_demandeur_id', $currentUser->service_id);
                });
            } elseif ($currentUser->hasRole('service-achat') || $currentUser->hasRole('responsable-budget')) {
                // Service achat and budget managers can see all commands
                // No filter needed
            }
        }

        return $query->with(['demandeDevis', 'demandeDevis.serviceDemandeur', 'livraison']);
    }
}
