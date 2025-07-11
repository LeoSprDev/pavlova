<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BudgetCompletExport implements WithMultipleSheets
{
    public function __construct(public ?int $serviceId = null)
    {
    }

    public function sheets(): array
    {
        return [
            new BudgetSyntheseSheet($this->serviceId),
            new BudgetServiceDetailSheet($this->serviceId),
        ];
    }
}
