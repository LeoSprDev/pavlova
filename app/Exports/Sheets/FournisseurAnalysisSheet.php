<?php
namespace App\Exports\Sheets;

use App\Models\DemandeDevis;
use Maatwebsite\Excel\Concerns\{FromCollection, WithTitle, WithHeadings, ShouldAutoSize, WithStyles};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FournisseurAnalysisSheet implements FromCollection, WithTitle, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $dateDebut;
    protected $dateFin;
    protected array $options;

    public function __construct($dateDebut, $dateFin, array $options)
    {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->options = $options;
    }

    public function collection()
    {
        return DemandeDevis::selectRaw('fournisseur_propose, SUM(prix_total_ttc) as total, COUNT(*) as nb, AVG(DATEDIFF(date_validation_achat, created_at)) as delai')
            ->whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->whereNotNull('fournisseur_propose')
            ->groupBy('fournisseur_propose')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn($f) => [
                'Fournisseur' => $f->fournisseur_propose,
                'Montant' => $f->total,
                'Nb Commandes' => $f->nb,
                'Délai Moyen' => round($f->delai,1),
            ]);
    }

    public function headings(): array
    {
        return ['Fournisseur', 'Montant', 'Nb Commandes', 'Délai Moyen'];
    }

    public function title(): string
    {
        return 'Fournisseurs';
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
