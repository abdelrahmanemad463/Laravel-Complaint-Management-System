<?php

namespace App\Exports\Visitors;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Chunk read filter so a large workbook is only ever held in memory in slices.
 */
class MasterDataChunkReadFilter implements IReadFilter
{
    private int $startRow = 1;
    private int $endRow = 1;
    private array $columns;

    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    public function setRows(int $startRow, int $endRow): void
    {
        $this->startRow = $startRow;
        $this->endRow = $endRow;
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        if ($row >= $this->startRow && $row <= $this->endRow) {
            if (in_array($columnAddress, $this->columns, true)) {
                return true;
            }
        }

        return false;
    }
}
