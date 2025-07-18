<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations personnelles')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Adresse email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email vérifié le')
                            ->visible(fn () => optional(auth()->user())->hasRole('administrateur')),
                    ])->columns(2),

                Forms\Components\Section::make('Mot de passe')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->helperText(fn (string $operation): string => 
                                $operation === 'edit' 
                                    ? 'Laissez vide pour conserver le mot de passe actuel' 
                                    : 'Saisissez un mot de passe pour le nouvel utilisateur'
                            ),
                        Forms\Components\Toggle::make('force_password_change')
                            ->label('Forcer le changement de mot de passe à la prochaine connexion')
                            ->helperText('L\'utilisateur devra changer son mot de passe lors de sa prochaine connexion')
                            ->visible(fn () => optional(auth()->user())->hasRole('administrateur')),
                    ])->columns(1),

                Forms\Components\Section::make('Service et rôles')
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->relationship('service', 'nom')
                            ->label('Service')
                            ->searchable()
                            ->preload()
                            ->disabled(fn () => !optional(auth()->user())->hasRole('administrateur')),
                        Forms\Components\Toggle::make('is_service_responsable')
                            ->label('Responsable de service')
                            ->helperText('Cochez si cet utilisateur est responsable de son service')
                            ->visible(fn () => optional(auth()->user())->hasRole('administrateur')),
                        Forms\Components\CheckboxList::make('user_roles')
                            ->label('Rôles')
                            ->options(function () {
                                return \Spatie\Permission\Models\Role::all()->mapWithKeys(function ($role) {
                                    $descriptions = [
                                        'administrateur' => 'Administrateur - Accès complet au système',
                                        'responsable-budget' => 'Responsable Budget - Gestion des budgets et validation',
                                        'service-achat' => 'Service Achat - Gestion des achats et commandes',
                                        'responsable-service' => 'Responsable Service - Responsable d\'un service',
                                        'agent-service' => 'Agent Service - Agent d\'un service',
                                        'service-demandeur' => 'Service Demandeur - Peut créer des demandes',
                                    ];
                                    $description = $descriptions[$role->name] ?? '';
                                    return [$role->name => $description ? "{$role->name} - {$description}" : $role->name];
                                });
                            })
                            ->afterStateHydrated(function (Forms\Components\CheckboxList $component, $record) {
                                if ($record) {
                                    $component->state($record->roles->pluck('name')->toArray());
                                }
                            })
                            ->dehydrated(true)  // Changed to true so it's included in form data
                            ->helperText('Sélectionnez un ou plusieurs rôles pour cet utilisateur')
                            ->columns(1)
                            ->visible(fn () => optional(auth()->user())->hasRole('administrateur')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('service.nom')
                    ->label('Service')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->separator(',')
                    ->colors([
                        'danger' => 'administrateur',
                        'warning' => 'responsable-budget',
                        'success' => 'responsable-service',
                        'info' => 'service-achat',
                        'gray' => 'agent-service',
                        'primary' => 'service-demandeur',
                    ]),
                Tables\Columns\IconColumn::make('is_service_responsable')
                    ->label('Resp. Service')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => 
                        optional(auth()->user())->hasRole('administrateur') ||
                        (optional(auth()->user())->hasRole('responsable-service') && 
                         optional(auth()->user())->service_id === $record->service_id)
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => optional(auth()->user())->hasRole('administrateur')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $currentUser = auth()->user();

        if ($currentUser && !$currentUser->hasRole('administrateur')) {
            if ($currentUser->hasRole('responsable-service') && $currentUser->service_id) {
                // Responsable de service ne voit que les utilisateurs de son service
                $query->where('service_id', $currentUser->service_id);
            } elseif ($currentUser->hasAnyRole(['agent-service', 'service-demandeur']) && $currentUser->service_id) {
                // Agent de service ne voit que les utilisateurs de son service
                $query->where('service_id', $currentUser->service_id);
            } elseif ($currentUser->hasRole('responsable-budget')) {
                // Responsable budget voit tous les utilisateurs (pas de filtre)
            } elseif ($currentUser->hasRole('service-achat')) {
                // Service achat voit tous les utilisateurs (pas de filtre)
            } else {
                // Autres rôles : ne voient que leur propre profil
                $query->where('id', $currentUser->id);
            }
        }
        // Administrateur voit tous les utilisateurs (pas de filtre)

        return $query->with(['service', 'roles']);
    }
}
