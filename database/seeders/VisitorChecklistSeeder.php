<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{VisitorVisitType, VisitorSection, VisitorChecklistItem};

class VisitorChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            ['Receiving Materials', 'receiving'],
            ['Storage', 'storage'],
            ['Food Handling', 'food_handling'],
            ['Quality', 'quality'],
            ['Pest Control', 'pest_control'],
            ['Cleaning & Sanitization', 'cleaning'],
            ['Employees', 'employees'],
            ['Maintenance', 'maintenance'],
            ['Infrastructure', 'infrastructure'],
            ['Occupational Safety', 'occupational_safety'],
        ];
        $sectionIds = [];
        $order = 0;
        foreach ($sections as [$name, $code]) {
            $sectionIds[$code] = VisitorSection::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'sort_order' => $order++, 'is_active' => true]
            )->id;
        }

        // Daily Inspection checklist (representative sample)
        $daily = VisitorVisitType::where('code', 'daily')->first();
        $monthly = VisitorVisitType::where('code', 'monthly')->first();
        $safety = VisitorVisitType::where('code', 'occupational_safety')->first();

        // [type, sectionCode, code, title, severity, deduction]
        $items = [
            [$daily, 'receiving', 'DY-001', 'Raw materials are received from approved suppliers', 'critical', 5],
            [$daily, 'receiving', 'DY-002', 'Incoming goods are checked for temperature on arrival', 'major', 3],
            [$daily, 'storage', 'DY-003', 'Cold storage temperature is within required range', 'critical', 5],
            [$daily, 'storage', 'DY-004', 'Dry storage is clean and pest-free', 'major', 3],
            [$daily, 'food_handling', 'DY-005', 'Food handling staff follow hand-washing procedures', 'critical', 5],
            [$daily, 'quality', 'DY-006', 'Finished products meet quality specifications', 'major', 3],
            [$daily, 'cleaning', 'DY-007', 'Production surfaces are cleaned and sanitized', 'minor', 1],
            [$monthly, 'receiving', 'MO-001', 'Supplier approval records are up to date', 'critical', 10],
            [$monthly, 'storage', 'MO-002', 'Stock rotation (FIFO) is correctly applied', 'major', 5],
            [$monthly, 'quality', 'MO-003', 'Monthly quality audits performed and documented', 'major', 5],
            [$monthly, 'employees', 'MO-004', 'Training records are complete and current', 'minor', 2],
            [$monthly, 'maintenance', 'MO-005', 'Preventive maintenance schedule is followed', 'major', 5],
            [$safety, 'occupational_safety', 'OS-001', 'Fire extinguishers are accessible and inspected', 'critical', 15],
            [$safety, 'occupational_safety', 'OS-002', 'Emergency exits are unobstructed and marked', 'critical', 15],
            [$safety, 'occupational_safety', 'OS-003', 'Personal protective equipment is available and used', 'major', 8],
            [$safety, 'occupational_safety', 'OS-004', 'First aid kits are stocked and accessible', 'major', 8],
            [$safety, 'infrastructure', 'OS-005', 'Electrical panels are labelled and accessible', 'minor', 3],
        ];

        foreach ($items as $i => [$type, $sectionCode, $code, $title, $severity, $deduction]) {
            if (!$type) continue;
            VisitorChecklistItem::updateOrCreate(
                ['code' => $code, 'visit_type_id' => $type->id],
                [
                    'section_id' => $sectionIds[$sectionCode],
                    'title' => $title,
                    'severity' => $severity,
                    'deduction_score' => $deduction,
                    'photo_required' => $severity === 'critical',
                    'immediate_action' => 'Isolate the non-conforming item and inform the responsible party immediately.',
                    'corrective_action' => 'Determine the root cause and apply a corrective action to prevent recurrence.',
                    'preventive_action' => 'Review procedures and provide retraining where required.',
                    'responsible' => 'Branch Manager',
                    'deadline' => '2 days',
                    'sort_order' => $i,
                    'is_active' => true,
                ]
            );
        }
    }
}
