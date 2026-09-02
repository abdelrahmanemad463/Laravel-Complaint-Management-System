<?php

namespace App\Services\Visitors;

use App\Exports\Visitors\MasterDataChunkReadFilter;
use App\Models\VisitorChecklistItem;
use App\Models\VisitorImport;
use App\Models\VisitorImportRow;
use App\Models\VisitorSection;
use App\Models\VisitorSeverity;
use App\Models\VisitorVisitType;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Excel master-data management for the Quality Visits inspection checklist.
 *
 * The uploaded file is validated, chunk-read, per-row validated and previewed
 * BEFORE any database mutation. Only an explicit confirmation imports the data
 * using a safe create/update upsert keyed on (inspection_type code + code).
 * Historical visit data is never modified (visitors_visit_items snapshots).
 */
class VisitorMasterDataService
{
    public const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50 MB

    private const HEADERS = [
        'code', 'inspection_type', 'section', 'note', 'severity',
        'immediate_action', 'corrective_action', 'responsible', 'period',
        'preventive_action', 'deduction_score',
    ];

    private const CHUNK_SIZE = 200;

    /**
     * Validate the uploaded file (type + size + real MIME, not just extension).
     *
     * @return array{ok:bool, errors:array<int,string>}
     */
    public function validateUpload(?object $file): array
    {
        if (!$file || !method_exists($file, 'getRealPath')) {
            return ['ok' => false, 'errors' => ['A file is required.']];
        }

        if (!$file->isValid()) {
            return ['ok' => false, 'errors' => ['The uploaded file is invalid.']];
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return ['ok' => false, 'errors' => ['Maximum allowed file size is 50 MB.']];
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            return ['ok' => false, 'errors' => ['Only .xlsx and .xls files are supported.']];
        }

        // Verify the real MIME type rather than trusting the extension.
        $realType = $file->getMimeType();
        $allowed = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
            'application/vnd.ms-excel',                                          // xls
            'application/octet-stream',
            'application/zip',
        ];
        if (!in_array($realType, $allowed, true)) {
            return ['ok' => false, 'errors' => ['The file type is not a valid Excel document.']];
        }

        return ['ok' => true, 'errors' => []];
    }

    /**
     * Chunk-read an uploaded Excel file into normalized heading-keyed rows.
     *
     * @return array<int,array<string,mixed>>
     */
    public function readRows(string $path, string $extension): array
    {
        $headers = $this->readHeaderRow($path, $extension);
        if (empty($headers)) {
            return [];
        }

        $columnLetters = $this->columnLettersFromHeader($headers);
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $chunk = new MasterDataChunkReadFilter($columnLetters);
        $reader->setReadFilter($chunk);

        $rows = [];
        $highestRow = $this->highestDataRow($path, $extension, count($headers));

        for ($start = 2; $start <= $highestRow; $start += self::CHUNK_SIZE) {
            $end = min($start + self::CHUNK_SIZE - 1, $highestRow);
            $chunk->setRows($start, $end);
            $sheet = $reader->load($path)->getActiveSheet();
            $data = $sheet->rangeToArray("A{$start}:".$columnLetters[count($columnLetters) - 1].$end, '', true, false);
            foreach ($data as $row) {
                $assoc = $this->assocRows($headers, $row);
                // Skip fully-empty rows.
                if (implode('', array_map('trim', array_map('strval', $assoc))) === '') {
                    continue;
                }
                $rows[] = $assoc;
            }
        }

        return $rows;
    }

    /**
     * Validate/normalize rows against the master-data lookups and detect
     * duplicate (inspection_type, code) pairs inside the uploaded file.
     *
     * @return array{valid:array<int,array>,invalid:array<int,array>}
     */
    public function validateRows(array $rows): array
    {
        $types = $this->lookupTypeMap();
        $severities = $this->lookupSeverityMap();

        $seen = [];
        $valid = [];
        $invalid = [];

        foreach ($rows as $index => $row) {
            $errors = [];
            $normalized = $this->normalize($row);

            if ($normalized['code'] === '') {
                $errors[] = 'code is required.';
            }
            if ($normalized['inspection_type'] === '') {
                $errors[] = 'inspection_type is required.';
            } elseif (!isset($types[strtolower($normalized['inspection_type'])])) {
                $errors[] = 'Invalid inspection_type.';
            }
            if ($normalized['section'] === '') {
                $errors[] = 'Section is required.';
            }
            if ($normalized['note'] === '') {
                $errors[] = 'Note is required.';
            }
            if ($normalized['severity'] === '') {
                $errors[] = 'severity is required.';
            } elseif (!isset($severities[strtolower($normalized['severity'])])) {
                $errors[] = 'Invalid severity.';
            }
            if (!is_numeric($normalized['deduction_score']) || $normalized['deduction_score'] < 0) {
                $errors[] = 'Deduction score must be a number.';
            }

            if ($errors === [] && $normalized['inspection_type'] !== '' && $normalized['code'] !== '') {
                $typeKey = strtolower($normalized['inspection_type']);
                $key = $typeKey.'|'.$normalized['code'];
                if (isset($seen[$key])) {
                    $errors[] = 'Duplicate code for this inspection_type within the file.';
                } else {
                    $seen[$key] = true;
                }
            }

            $entry = [
                'row_number' => $index + 2, // +2: header row + zero-based index
                'normalized' => $normalized,
                'code' => $normalized['code'],
                'inspection_type' => $normalized['inspection_type'],
            ];

            if ($errors === []) {
                $valid[] = $entry;
            } else {
                $entry['errors'] = array_values(array_unique($errors));
                $invalid[] = $entry;
            }
        }

        return ['valid' => $valid, 'invalid' => $invalid];
    }

    /**
     * Persist an import header + per-row results. Does NOT touch master data.
     */
    public function storeImport(object $file, int $userId, array $rows, array $validation): VisitorImport
    {
        $total = count($validation['valid']) + count($validation['invalid']);
        $hasErrors = $validation['invalid'] !== [];

        $import = VisitorImport::create([
            'user_id' => $userId,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'extension' => strtolower($file->getClientOriginalExtension() ?: ''),
            'status' => $hasErrors ? 'ready' : 'ready', // preview is shown either way; confirmation refused if invalid
            'total_rows' => $total,
            'valid_rows' => count($validation['valid']),
            'invalid_rows' => count($validation['invalid']),
        ]);

        foreach ($validation['valid'] as $entry) {
            VisitorImportRow::create([
                'import_id' => $import->id,
                'row_number' => $entry['row_number'],
                'code' => $entry['code'],
                'inspection_type' => $entry['inspection_type'],
                'valid' => true,
                'errors' => null,
                'data' => json_encode($entry['normalized']),
            ]);
        }
        foreach ($validation['invalid'] as $entry) {
            VisitorImportRow::create([
                'import_id' => $import->id,
                'row_number' => $entry['row_number'],
                'code' => $entry['code'],
                'inspection_type' => $entry['inspection_type'],
                'valid' => false,
                'errors' => json_encode($entry['errors']),
                'data' => json_encode($entry['normalized']),
            ]);
        }

        return $import->fresh();
    }

    /**
     * Apply a ready import: create/update inspection items inside a transaction.
     * Refuses to import when any validation errors are present (whole-file reject).
     *
     * @return array{created:int,updated:int}
     */
    public function confirm(VisitorImport $import): array
    {
        if (!$import->isReady()) {
            throw new \RuntimeException('This import is not waiting for confirmation.');
        }
        if ($import->invalid_rows > 0) {
            throw new \RuntimeException('This import contains validation errors and cannot be confirmed.');
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($import, &$created, &$updated) {
            $types = $this->lookupTypeMap();
            $severities = $this->lookupSeverityMap();
            $sectionCache = [];

            $order = 0;
            $import->rows()->where('valid', true)->get()->each(function (VisitorImportRow $row) use (
                &$created, &$updated, &$order, $types, $severities, &$sectionCache, $import
            ) {
                $data = $row->dataArray();
                $type = $types[strtolower($data['inspection_type'] ?? '')] ?? null;
                if (!$type) {
                    $import->increment('failed_records');
                    return;
                }

                $severityCode = $severities[strtolower($data['severity'] ?? '')] ?? null;
                $sectionName = trim((string) ($data['section'] ?? ''));
                $section = $sectionCache[$sectionName] ??= VisitorSection::firstOrCreate(
                    ['name' => $sectionName],
                    ['is_active' => true, 'sort_order' => 0]
                );

                $payload = [
                    'visit_type_id' => $type->id,
                    'section_id' => $section->id,
                    'title' => trim((string) ($data['note'] ?? '')),
                    'severity' => $severityCode ? $severityCode->code : 'major',
                    'deduction_score' => max(0, round((float) ($data['deduction_score'] ?? 0))),
                    'photo_required' => ($severityCode ? $severityCode->code : '') === 'critical',
                    'immediate_action' => $this->blank($data['immediate_action'] ?? ''),
                    'corrective_action' => $this->blank($data['corrective_action'] ?? ''),
                    'preventive_action' => $this->blank($data['preventive_action'] ?? ''),
                    'responsible' => $this->blank($data['responsible'] ?? ''),
                    'deadline' => $this->blank($data['period'] ?? ''),
                    'is_active' => true,
                    'sort_order' => $order++,
                ];

                $existing = VisitorChecklistItem::where('visit_type_id', $type->id)
                    ->where('code', trim((string) ($data['code'] ?? '')))
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                    $row->update(['updated' => true, 'created' => false]);
                } else {
                    VisitorChecklistItem::create($payload + ['code' => trim((string) ($data['code'] ?? ''))]);
                    $created++;
                    $row->update(['created' => true, 'updated' => false]);
                }
            });

            $import->update([
                'created_records' => $created,
                'updated_records' => $updated,
                'status' => 'imported',
                'completed_at' => now(),
            ]);
        });

        return ['created' => $created, 'updated' => $updated];
    }

    public function cancel(VisitorImport $import): void
    {
        if ($import->isReady() || $import->isPending()) {
            $import->update(['status' => 'cancelled', 'completed_at' => now()]);
        }
    }

    /**
     * Turn an assoc row into a normalized array aligned to the expected columns.
     */
    private function normalize(array $row): array
    {
        $normalized = [
            'code' => trim((string) ($row['code'] ?? '')),
            'inspection_type' => trim((string) ($row['inspection_type'] ?? '')),
            'section' => trim((string) ($row['section'] ?? '')),
            'note' => trim((string) ($row['note'] ?? '')),
            'severity' => trim((string) ($row['severity'] ?? '')),
            'immediate_action' => trim((string) ($row['immediate_action'] ?? '')),
            'corrective_action' => trim((string) ($row['corrective_action'] ?? '')),
            'responsible' => trim((string) ($row['responsible'] ?? '')),
            'period' => trim((string) ($row['period'] ?? '')),
            'preventive_action' => trim((string) ($row['preventive_action'] ?? '')),
            'deduction_score' => trim((string) ($row['deduction_score'] ?? '')),
        ];

        if ($normalized['deduction_score'] !== '') {
            $normalized['deduction_score'] = is_numeric($normalized['deduction_score'])
                ? (float) $normalized['deduction_score']
                : $normalized['deduction_score'];
        }

        return $normalized;
    }

    private function blank(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function assocRows(array $headers, array $row): array
    {
        $out = [];
        foreach ($headers as $i => $header) {
            $out[$header] = $row[$i] ?? '';
        }

        return $out;
    }

    private function columnLettersFromHeader(array $headers): array
    {
        $letters = [];
        for ($i = 0; $i < count($headers); $i++) {
            $letters[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
        }

        return $letters;
    }

    private function readHeaderRow(string $path, string $extension): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $sheet = $reader->load($path)->getActiveSheet();
        $first = $sheet->rangeToArray('A1:K1', '', true, false)[0] ?? [];
        $first = array_map(fn ($v) => strtolower(trim((string) $v)), $first);
        // Keep only known headers, in file order, drop unknown/empty.
        $known = array_values(array_filter($first, fn ($v) => in_array($v, self::HEADERS, true) && $v !== ''));
        // If none of the first 11 matched, fall back to the canonical order.
        return $known ?: self::HEADERS;
    }

    private function highestDataRow(string $path, string $extension, int $columns): int
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();

        return $sheet->getHighestDataRow();
    }

    /**
     * Map lowercased code OR lowercased display name -> model.
     */
    private function lookupTypeMap(): array
    {
        $map = [];
        foreach (VisitorVisitType::where('is_active', true)->get() as $type) {
            $map[strtolower($type->code)] = $type;
            $map[strtolower($type->name)] = $type;
        }

        return $map;
    }

    private function lookupSeverityMap(): array
    {
        $map = [];
        foreach (VisitorSeverity::where('is_active', true)->get() as $se) {
            $map[strtolower($se->code)] = $se;
            $map[strtolower($se->name)] = $se;
        }

        return $map;
    }
}
