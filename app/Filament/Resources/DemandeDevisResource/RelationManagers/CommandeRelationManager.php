<?php

namespace App\Filament\Resources\DemandeDevisResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Commande;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;

class CommandeRelationManager extends RelationManager
{
    protected static string $relationship = 'commande';

    protected static ?string $recordTitleAttribute = 'numero_commande';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('numero_commande')
                    ->label('Numéro de Commande')
                    ->disabled()
                    ->helperText('Généré automatiquement'),
                DatePicker::make('date_commande')
                    ->label('Date de Commande')
                    ->required()
                    ->default(now()),
                TextInput::make('commanditaire')
                    ->label('Commanditaire')
                    ->required()
                    ->maxLength(255)
                    ->default(fn() => Auth::user()->name),
                Select::make('statut')
                    ->label('Statut')
                    ->options([
                        'en_attente' => 'En Attente',
                        'confirmee' => 'Confirmée',
                        'expediee' => 'Expédiée',
                        'livree' => 'Livrée',
                        'annulee' => 'Annulée',
                    ])
                    ->default('en_attente')
                    ->required()
                    ->native(false),
                DatePicker::make('date_livraison_prevue')
                    ->label('Date de Livraison Prévue')
                    ->required(),
                TextInput::make('montant_reel')
                    ->label('Montant Réel')
                    ->numeric()
                    ->prefix('€')
                    ->step(0.01),
                TextInput::make('fournisseur_contact')
                    ->label('Contact Fournisseur')
                    ->maxLength(255),
                TextInput::make('fournisseur_email')
                    ->label('Email Fournisseur')
                    ->email()
                    ->maxLength(255),
                Textarea::make('conditions_paiement')
                    ->label('Conditions de Paiement')
                    ->rows(3),
                TextInput::make('delai_livraison')
                    ->label('Délai de Livraison')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_commande')
            ->columns([
                TextColumn::make('numero_commande')
                    ->label('N° Commande')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date_commande')
                    ->label('Date Commande')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('commanditaire')
                    ->label('Commanditaire')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('statut')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->colors([
                        'warning' => 'en_attente',
                        'info' => 'confirmee',
                        'primary' => 'expediee',
                        'success' => 'livree',
                        'danger' => 'annulee',
                    ])
                    ->sortable(),
                TextColumn::make('date_livraison_prevue')
                    ->label('Livraison Prévue')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('montant_reel')
                    ->label('Montant Réel')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('fournisseur_contact')
                    ->label('Contact Fournisseur')
                    ->searchable()
                    ->limit(30),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->options([
                        'en_attente' => 'En Attente',
                        'confirmee' => 'Confirmée',
                        'expediee' => 'Expédiée',
                        'livree' => 'Livrée',
                        'annulee' => 'Annulée',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn() => Auth::user()->hasRole('service-achat')),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn() => Auth::user()->hasRole('service-achat')),
                DeleteAction::make()
                    ->visible(fn() => Auth::user()->hasRole('service-achat')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->hasRole('service-achat')),
                ]),
            ]);
    }
}