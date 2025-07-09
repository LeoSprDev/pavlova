<?php
namespace App\Filament\Resources;

use Filament\Resources\Resource;
use App\Models\DemandeDevis;
use App\Models\BudgetLigne;
use Filament\Tables;
use Filament\Forms;
use Filament\Forms\Form;
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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('service_demandeur_id')
                ->relationship('serviceDemandeur', 'nom')
                ->default(auth()->user()->service_id)
                ->disabled()
                ->required(),

            Forms\Components\Select::make('budget_ligne_id')
                ->relationship('budgetLigne', 'intitule',
                    fn (Builder $query) => $query->where('service_id', auth()->user()->service_id)
                        ->where('valide_budget', 'oui')
                )
                ->required()
                ->live()
                ->afterStateUpdated(fn ($state, callable $set) =>
                    $set('budget_disponible',
                        BudgetLigne::find($state)?->calculateBudgetRestant() ?? 0
                    )
                ),

            Forms\Components\TextInput::make('denomination')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('quantite')
                ->numeric()
                ->default(1)
                ->required(),

            Forms\Components\TextInput::make('prix_unitaire_ht')
                ->numeric()
                ->prefix('€')
                ->live()
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    $quantite = $get('quantite') ?? 1;
                    $prix_ht = $state * $quantite;
                    $set('prix_total_ht', $prix_ht);
                    $set('prix_total_ttc', $prix_ht * 1.2);
                }),

            Forms\Components\Textarea::make('justification_achat')
                ->required()
                ->rows(3),

            Forms\Components\Placeholder::make('budget_disponible')
                ->label('Budget restant sur cette ligne')
                ->content(fn ($get) =>
                    number_format($get('budget_disponible') ?? 0, 2) . ' €'
                ),
        ]);
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
                        'secondary' => 'pending',
                        'warning' => 'approved_service',
                        'info' => 'approved_budget',
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
