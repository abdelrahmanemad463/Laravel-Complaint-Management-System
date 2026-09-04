<?php

namespace App\Services\Visitors;

use App\Models\VisitorChecklistItem;
use App\Models\VisitorSection;
use App\Models\VisitorVisitType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Imports an Excel checklist configuration into the runtime database.
 * Excel is only a configuration mechanism; the database is the runtime source.
 */
class ChecklistImportService
{
    /**
     * @param Collection $rows rows from an import class (withHeadingRow)
     * @param string $visitTypeCode which visit type the checklist belongs to
     * @return array{created:int,updated:int}
     */
    public function import(Collection $rows, string $visitTypeCode): array
    {
        $type = VisitorVisitType::where('code', $visitTypeCode)->firstOrFail();

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($rows, $type, &$created, &$updated) {
            $sectionCache = [];

            foreach ($rows as $row) {
                $sectionName = trim((string) ($row['section'] ?? ''));
                if ($sectionName === '') continue;

                $section = $sectionCache[$sectionName] ??= VisitorSection::firstOrCreate(
                    ['name' => $sectionName],
                    ['is_active' => true, 'sort_order' => 0]
                );

                $code = trim((string) ($row['code'] ?? ''));
                $title = trim((string) ($row['note'] ?? $row['item'] ?? $row['observation'] ?? ''));
                $severity = strtolower(trim((string) ($row['severity'] ?? 'major')));
                if (!in_array($severity, ['critical', 'major', 'minor'], true)) {
                    $severity = 'major';
                }
                $deduction = max(0, (int) ($row['deduction_score'] ?? $row['deduction'] ?? 0));

                $payload = [
                    'visit_type_id' => $type->id,
                    'section_id' => $section->id,
                    'title' => $title,
                    'severity' => $severity,
                    'deduction_score' => $deduction,
                    'photo_required' => $severity === 'critical',
                    'immediate_action' => trim((string) ($row['immediate_action'] ?? '')) ?: null,
                    'corrective_action' => trim((string) ($row['corrective_action'] ?? '')) ?: null,
                    'preventive_action' => trim((string) ($row['preventive_action'] ?? '')) ?: null,
                    'responsible' => trim((string) ($row['responsible'] ?? '')) ?: null,
                    'period_hours' => $this->periodHours($row['period_hours'] ?? $row['period'] ?? ''),
                    'is_active' => true,
                ];

                if ($code !== '') {
                    VisitorChecklistItem::updateOrCreate(
                        ['visit_type_id' => $type->id, 'code' => $code],
                        $payload
                    );
                    $updated++;
                } else {
                    VisitorChecklistItem::create($payload);
                    $created++;
                }
            }
        });

        return ['created' => $created, 'updated' => $updated];
    }

    private function periodHours(mixed $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }
}
