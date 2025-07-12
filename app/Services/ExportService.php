<?php
namespace App\Services;

use App\Exports\BudgetCompletExport;
use App\Models\User;
use App\Services\ExecutivePDFExport;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExportService
{
    public function generateExecutiveReport(User $user, array $options = []): string
    {
        return app(ExecutivePDFExport::class)->generate($user, $options);
    }

    public function createMultiFormatExport(User $user, array $options = [], string $format = 'excel'): Response
    {
        $filename = 'budget_export_' . now()->format('Y-m-d_H-i');

        if ($format === 'pdf') {
            $content = $this->generateExecutiveReport($user, $options);
            return response()->streamDownload(fn() => print($content), "$filename.pdf");
        }

        $export = new BudgetCompletExport($user, $options);
        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        return Excel::download($export, "$filename." . ($writerType === ExcelWriter::CSV ? 'csv' : 'xlsx'), $writerType);
    }

    public function addExcelCharts(Spreadsheet $spreadsheet, array $data): void
    {
        // Placeholder for advanced charting logic
    }
}
