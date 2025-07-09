<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informations Service')->schema([
                Forms\Components\TextInput::make('nom')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(10)
                    ->placeholder('Ex: IT, RH, MKT'),
                Forms\Components\TextInput::make('responsable_email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('budget_annuel_alloue')
                    ->numeric()
                    ->prefix('€')
                    ->step(0.01),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('actif')
                    ->default(true)
                    ->helperText('Décocher pour désactiver sans supprimer'),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nom')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('code')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('responsable_email')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('budget_annuel_alloue')
                ->money('EUR')
                ->sortable()
                ->toggleable(),
            BadgeColumn::make('actif')
                ->colors([
                    'success' => true,
                    'danger' => false,
                ])
                ->formatStateUsing(fn ($state) => $state ? 'Actif' : 'Inactif'),
            Tables\Columns\TextColumn::make('users_count')
                ->counts('users')
                ->label('Nb Utilisateurs'),
            Tables\Columns\TextColumn::make('budget_lignes_count')
                ->counts('budgetLignes')
                ->label('Nb Lignes Budget'),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Filter::make('actifs')
                ->query(fn (Builder $query): Builder => $query->where('actif', true)),
            Filter::make('inactifs')
                ->query(fn (Builder $query): Builder => $query->where('actif', false)),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Action::make('view_users')
                ->icon('heroicon-o-users')
                ->label('Utilisateurs')
                ->url(fn (Service $record) => '/admin/users?tableFilters[service][value]=' . $record->id),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
