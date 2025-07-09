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
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Spatie\Permission\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\CreateRecord;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informations Utilisateur')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                    ->dehydrateStateUsing(fn ($state) => $state ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state)),
            ]),
            
            Section::make('Affectation Service')->schema([
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'nom')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Forms\Components\Toggle::make('is_service_responsable')
                    ->label('Responsable de service')
                    ->helperText('Peut valider les demandes des agents de son service'),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->options(function () {
                        return Role::all()->pluck('name', 'name')->toArray();
                    })
                    ->label('Rôles'),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('email')
                ->searchable(),
            Tables\Columns\TextColumn::make('service.nom')
                ->label('Service')
                ->sortable(),
            BadgeColumn::make('is_service_responsable')
                ->label('Responsable')
                ->colors([
                    'success' => true,
                    'secondary' => false,
                ])
                ->formatStateUsing(fn ($state) => $state ? 'Responsable' : 'Agent'),
            Tables\Columns\TextColumn::make('roles.name')
                ->badge()
                ->label('Rôles'),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime('d/m/Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            SelectFilter::make('service')
                ->relationship('service', 'nom')
                ->label('Service'),
            Filter::make('responsables')
                ->label('Responsables uniquement')
                ->query(fn (Builder $query) => $query->where('is_service_responsable', true)),
            SelectFilter::make('roles')
                ->relationship('roles', 'name')
                ->label('Rôle'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
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
}
