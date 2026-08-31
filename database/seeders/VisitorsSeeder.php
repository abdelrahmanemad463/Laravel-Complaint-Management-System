<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{VisitorVisitType, VisitorRootCause};
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
    }
}
