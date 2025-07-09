<?php
namespace App\Filament\Resources;

use App\Filament\Resources\LivraisonResource\Pages;
use App\Models\Livraison;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class LivraisonResource extends Resource
{
    protected static ?string $model = Livraison::class;
    protected static ?string $navigationGroup = 'Achats & Dépenses';
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations Commande')
                ->schema([
                    Forms\Components\Select::make('commande_id')
                        ->relationship('commande', 'numero_commande')
                        ->required()
                        ->disabled(fn ($operation) => $operation === 'edit'),
                    Forms\Components\DatePicker::make('date_livraison_prevue')
                        ->required(),
                    Forms\Components\DatePicker::make('date_livraison_reelle')
                        ->required(fn (Forms\Get $get) => $get('statut_reception') !== 'en_attente'),
                ]),

            Forms\Components\Section::make('📋 Documents OBLIGATOIRES')
                ->schema([
                    Forms\Components\FileUpload::make('bon_livraison')
                        ->label('Bon de livraison signé (OBLIGATOIRE)')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->required()
                        ->directory('bons-livraison')
                        ->downloadable()
                        ->openable()
                        ->maxSize(10240)
                        ->helperText('⚠️ OBLIGATOIRE : Bon de livraison signé pour finaliser'),

                    Forms\Components\FileUpload::make('photos_reception')
                        ->label('Photos réception matériel (optionnel)')
                        ->multiple()
                        ->acceptedFileTypes(['image/jpeg', 'image/png'])
                        ->directory('photos-reception')
                        ->maxSize(5120)
                        ->openable()
                        ->helperText('Photos du matériel reçu pour traçabilité'),
                ]),

            Forms\Components\Section::make('✅ Contrôle Qualité & Conformité')
                ->schema([
                    Forms\Components\Select::make('statut_reception')
                        ->options([
                            'en_attente' => '⏳ En attente',
                            'recu_partiellement' => '📦 Reçu partiellement',
                            'recu_conforme' => '✅ Reçu conforme',
                            'recu_avec_reserves' => '⚠️ Reçu avec réserves',
                        ])
                        ->required()
                        ->live()
                        ->native(false),

                    Forms\Components\Toggle::make('conforme')
                        ->label('Livraison conforme aux spécifications')
                        ->live()
                        ->required(fn (Forms\Get $get) => $get('statut_reception') !== 'en_attente'),

                    Forms\Components\Textarea::make('anomalies')
                        ->label('Anomalies constatées')
                        ->visible(fn (Forms\Get $get) => !$get('conforme'))
                        ->required(fn (Forms\Get $get) =>
                            !$get('conforme') && $get('statut_reception') !== 'en_attente'
                        )
                        ->rows(3)
                        ->placeholder('Décrivez précisément les anomalies constatées...'),

                    Forms\Components\Textarea::make('actions_correctives')
                        ->label('Actions correctives demandées')
                        ->visible(fn (Forms\Get $get) => !$get('conforme'))
                        ->rows(3)
                        ->placeholder('Actions à mener (retour, échange, avoir...)'),

                    Forms\Components\TextInput::make('verifie_par')
                        ->label('Vérifié par')
                        ->default(auth()->user()->name)
                        ->required(),
                ]),

            Forms\Components\Section::make('🏁 Finalisation')
                ->schema([
                    Forms\Components\Placeholder::make('validation_info')
                        ->label('Information')
                        ->content(new HtmlString('
                        <div class="p-4 bg-blue-50 rounded">
                            <p class="text-sm text-blue-800">
                                ✅ Une fois marqué "conforme", le budget sera automatiquement mis à jour<br>
                                📧 Des notifications seront envoyées au service demandeur et responsable budget
                            </p>
                        </div>
                    ')),
                ])
                ->visible(fn (Forms\Get $get) => $get('conforme')),
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
                Tables\Columns\TextColumn::make('date_livraison_prevue')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('statut_reception'),
                Tables\Columns\IconColumn::make('conforme')->boolean(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => auth()->user()->can('update', $record))
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole('agent-service') || auth()->user()->hasRole('service-demandeur')) {
            return $query->whereHas('commande.demandeDevis', function ($q) {
                $q->where('service_demandeur_id', auth()->user()->service_id);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLivraisons::route('/'),
            'create' => Pages\CreateLivraison::route('/create'),
            'view' => Pages\ViewLivraison::route('/{record}'),
            'edit' => Pages\EditLivraison::route('/{record}/edit'),
        ];
    }
}
