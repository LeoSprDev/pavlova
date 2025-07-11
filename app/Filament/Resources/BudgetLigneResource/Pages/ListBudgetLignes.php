<?php

namespace App\Filament\Resources\BudgetLigneResource\Pages;

use App\Filament\Resources\BudgetLigneResource;
use Filament\Actions;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BudgetCompletExport;
use App\Services\ExecutivePDFExport;
use Filament\Forms\Components\{DatePicker, Select, Toggle, Section, Grid};
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Widgets\BudgetGlobalStatsWidget; // Example, if you create such a widget
use App\Filament\Widgets\ServiceBudgetStatsWidget; // Example for service demandeur
use Illuminate\Support\Facades\Auth;

class ListBudgetLignes extends ListRecords
{
    protected static string $resource = BudgetLigneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export_budget_revolutionnaire')
                ->label('📊 Export Révolutionnaire')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Section::make('Configuration Export')->schema([
                        Grid::make(2)->schema([
                            Select::make('format')->label('Format')
                                ->options(['excel' => 'Excel', 'pdf' => 'PDF'])
                                ->default('excel'),
                            Select::make('periode')->label('Période')
                                ->options([
                                    'mois_courant' => 'Mois Courant',
                                    'trimestre_courant' => 'Trimestre Courant',
                                    'annee_courante' => 'Année Courante',
                                    'personnalise' => 'Personnalisée',
                                ])->default('annee_courante')->reactive(),
                        ]),
                        Grid::make(2)->schema([
                            DatePicker::make('date_debut')->visible(fn($get)=>$get('periode')==='personnalise'),
                            DatePicker::make('date_fin')->visible(fn($get)=>$get('periode')==='personnalise'),
                        ]),
                        Grid::make(2)->schema([
                            Toggle::make('inclure_workflow')->label('Workflow')->default(true),
                            Toggle::make('inclure_fournisseurs')->label('Fournisseurs')->default(true),
                        ]),
                    ])
                ])
                ->action(function(array $data){
                    $user = auth()->user();
                    $options = [
                        'inclure_workflow' => $data['inclure_workflow'] ?? true,
                        'inclure_fournisseurs' => $data['inclure_fournisseurs'] ?? true,
                        'periode' => $data['periode'] ?? 'annee_courante',
                        'date_debut' => $data['date_debut'] ?? null,
                        'date_fin' => $data['date_fin'] ?? null,
                    ];
                    $filename = 'budget_export_' . now()->format('Y-m-d_H-i');
                    try {
                        if ($data['format'] === 'pdf') {
                            return response()->streamDownload(function() use ($user,$options){
                                echo app(ExecutivePDFExport::class)->generate($user,$options);
                            }, $filename.'.pdf');
                        }
                        return Excel::download(new BudgetCompletExport($user,$options), $filename.'.xlsx');
                    } catch (\Exception $e) {
                        Notification::make()->title('Erreur Export')->body($e->getMessage())->danger()->send();
                    }
                })
                ->visible(fn()=> optional(auth()->user())->hasAnyRole(['responsable-budget','responsable-direction','service-achat']) ?? false)
        ];
    }

    protected function getHeaderWidgets(): array
    {
        $user = Auth::user();
        $widgets = [];

        // if ($user->hasRole('responsable-budget')) {
        //     $widgets[] = BudgetGlobalStatsWidget::class;
        // } elseif ($user->hasRole('service-demandeur') && $user->service_id) {
        //     // Pass service_id to the widget if it's designed to be specific
        //     $widgets[] = ServiceBudgetStatsWidget::make(['service_id' => $user->service_id]);
        // }
        // Add other widgets as needed, e.g. charts from the prompt.
        // For now, returning empty until those widgets are defined.
        return $widgets;
    }

     /**
     * Get the columns for the table. (For ListRecords\Concerns\HasTable)
     * This is often defined in the Resource's table method, but can be overridden.
     * For now, we rely on the Resource's table() method.
     */

    /**
     * Add default table sort
     */
    protected function getDefaultTableSortColumn(): ?string
    {
        return 'date_prevue';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }
}
