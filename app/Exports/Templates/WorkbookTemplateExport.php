<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorkbookTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly array $sheets,
    ) {
    }

    public function sheets(): array
    {
        return $this->sheets;
    }
}
