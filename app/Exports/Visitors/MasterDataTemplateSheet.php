<?php

namespace App\Exports\Visitors;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * A single Excel sheet holding a header row plus optional rows.
 * Used both for the empty template sheet and the labelled example sheet.
 */
class MasterDataTemplateSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(private array $headings, private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
