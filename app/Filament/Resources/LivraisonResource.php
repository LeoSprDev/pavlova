<?php
namespace App\Filament\Resources;

use App\Filament\Resources\LivraisonResource\Pages;
use App\Models\Livraison;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LivraisonResource extends Resource
{
    protected static ?string $model = Livraison::class;
    protected static ?string $navigationGroup = 'Achats & Dépenses';
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Détails Livraison')->schema([
                Forms\Components\Select::make('commande_id')
                    ->relationship('commande', 'numero_commande')
                    ->required()
                    ->searchable(),
                Forms\Components\DatePicker::make('date_livraison')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('statut_reception')
                    ->options([
                        'en_attente' => 'En attente',
                        'recue' => 'Reçue',
                        'probleme_signale' => 'Problème signalé',
                        'refusee' => 'Refusée'
                    ])
                    ->required()
                    ->live(),
                Forms\Components\Textarea::make('commentaire_reception')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('conforme')
                    ->label('Livraison conforme')
                    ->live(),
                Forms\Components\Textarea::make('actions_requises')
                    ->visible(fn(Forms\Get $get) => !$get('conforme'))
                    ->rows(2)
                    ->placeholder('Décrivez les actions nécessaires...'),
                Forms\Components\Toggle::make('litige_en_cours'),
                Forms\Components\Select::make('note_qualite')
                    ->options([
                        1 => '1 - Très mauvais',
                        2 => '2 - Mauvais',
                        3 => '3 - Moyen',
                        4 => '4 - Bon',
                        5 => '5 - Excellent'
                    ])
                    ->visible(fn(Forms\Get $get) => $get('conforme'))
            ]),
            Forms\Components\Section::make('Documents')->schema([
                Forms\Components\FileUpload::make('bon_livraison')
                    ->label('Bon de livraison')
                    ->collection('bons_livraison')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(5120)
                    ->downloadable()
                    ->openable(),
                Forms\Components\FileUpload::make('photos_reception')
                    ->label('Photos réception')
                    ->collection('photos_reception')
                    ->multiple()
                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                    ->maxSize(2048)
                    ->imageEditor()
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('commande.numero_commande')
                    ->label('N° Commande')
                    ->searchable(),
                Tables\Columns\TextColumn::make('commande.demandeDevis.denomination')
                    ->label('Produit')
                    ->limit(25),
                Tables\Columns\TextColumn::make('date_livraison')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('statut_reception')
                    ->colors([
                        'warning' => 'en_attente',
                        'success' => 'recue',
                        'danger' => ['probleme_signale', 'refusee']
                    ]),
                Tables\Columns\IconColumn::make('conforme')
                    ->boolean(),
                Tables\Columns\TextColumn::make('note_qualite')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        1, 2 => 'danger',
                        3 => 'warning',
                        4, 5 => 'success',
                        default => 'gray'
                    }),
                Tables\Columns\IconColumn::make('litige_en_cours')
                    ->boolean()
                    ->color(fn($state) => $state ? 'danger' : 'success')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut_reception'),
                Tables\Filters\Filter::make('litiges')
                    ->query(fn($query) => $query->where('litige_en_cours', true))
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => auth()->user()->can('update', $record))
            ]);
    }

    public static function getPages(): array
    {
        return [
            // 'index' => Pages\ListLivraisons::route('/'),
            // 'create' => Pages\CreateLivraison::route('/create'),
            // 'view' => Pages\ViewLivraison::route('/{record}'),
            // 'edit' => Pages\EditLivraison::route('/{record}/edit'),
        ];
    }
}
