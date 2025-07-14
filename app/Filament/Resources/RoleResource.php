<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Rôles & Permissions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du Rôle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du Rôle')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Ex: responsable-budget, service-achat'),
                            
                        Forms\Components\TextInput::make('guard_name')
                            ->label('Guard')
                            ->default('web')
                            ->required()
                            ->disabled()
                            ->helperText('Garde d\'authentification (web par défaut)'),
                    ])->columns(2),

                Forms\Components\Section::make('Permissions')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Permissions associées')
                            ->relationship('permissions', 'name')
                            ->options(Permission::all()->pluck('name', 'id'))
                            ->helperText('Sélectionnez les permissions à associer à ce rôle')
                            ->columns(2)
                            ->gridDirection('row'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom du Rôle')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('warning'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('with_users')
                    ->label('Avec utilisateurs')
                    ->query(fn (Builder $query): Builder => $query->whereHas('users')),
                    
                Tables\Filters\Filter::make('with_permissions')
                    ->label('Avec permissions')
                    ->query(fn (Builder $query): Builder => $query->whereHas('permissions')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->action(function (Role $record) {
                        // Détacher les utilisateurs avant suppression
                        $record->users()->detach();
                        $record->permissions()->detach();
                        $record->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['users', 'permissions']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('administrateur') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('administrateur') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('administrateur') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('administrateur') ?? false;
    }
}