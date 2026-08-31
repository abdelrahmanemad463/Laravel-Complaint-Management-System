<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{VisitorVisitType, VisitorRootCause, VisitorSeverity};
class VisitorsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['Daily Inspection', 'daily'], ['Monthly Inspection', 'monthly'], ['Occupational Safety Inspection', 'occupational_safety']] as [$name, $code]) {
            VisitorVisitType::updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }
        foreach (['Negligence', 'Training', 'Maintenance', 'Weak Supervision', 'Purchasing', 'Other'] as $name) {
            VisitorRootCause::updateOrCreate(
                ['code' => strtolower(str_replace(' ', '_', $name))],
                ['name' => $name, 'is_active' => true]
            );
        }
        // Stable severity levels. The deduction/score is configured independently
        // per inspection item (never derived from the severity name).
        foreach ([['Critical', 'critical', 1], ['Major', 'major', 2], ['Minor', 'minor', 3]] as [$name, $code, $order]) {
            VisitorSeverity::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'sort_order' => $order, 'is_active' => true]
            );
        }
    }
}
