<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\ComplaintSource;
use App\Models\ComplaintStatus;
use App\Models\ComplaintType;
use App\Models\Customer;
use App\Models\Priority;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $branches = Branch::query()->orderBy('id')->pluck('id')->values();
        $services = Service::query()->orderBy('id')->pluck('id')->values();
        $sources = ComplaintSource::query()->orderBy('id')->pluck('id')->values();
        $categories = ComplaintCategory::query()->orderBy('id')->pluck('id')->values();
        $types = ComplaintType::query()->orderBy('id')->pluck('id')->values();
        $priorities = Priority::query()->orderBy('level')->pluck('id')->values();
        $statuses = ComplaintStatus::query()->orderBy('sort_order')->pluck('id')->values();

        if ($branches->isEmpty() || $services->isEmpty() || $sources->isEmpty() || $categories->isEmpty() || $types->isEmpty() || $priorities->isEmpty() || $statuses->isEmpty()) {
            throw new \RuntimeException('Demo data requires seeded complaint master data.');
        }

        $namedCustomers = [
            1 => ['name' => 'Ahmed Mohamed', 'phone_primary' => '01012345678', 'phone_2' => '01112345678'],
            2 => ['name' => 'Mona Ali', 'phone_primary' => '01212345678', 'phone_2' => null],
            3 => ['name' => 'Omar Hassan', 'phone_primary' => '01512345678', 'phone_2' => '01099887766'],
        ];

        foreach (range(1, 100) as $number) {
            $customerData = $namedCustomers[$number] ?? [
                'name' => "Demo Customer {$number}",
                'phone_primary' => sprintf('010900%05d', $number),
                'phone_2' => $number % 2 === 0 ? sprintf('011900%05d', $number) : null,
            ];

            $customer = Customer::firstOrCreate(
                ['phone_primary' => $customerData['phone_primary']],
                [
                    'name' => $customerData['name'],
                    'phone_2' => $customerData['phone_2'],
                    'address' => "Demo Street {$number}",
                ],
            );

            $shortDescription = $number <= 3
                ? 'Wrong order received'
                : sprintf('Demo complaint #%03d', $number);

            Complaint::firstOrCreate(
                [
                    'customer_id' => $customer->id,
                    'short_description' => $shortDescription,
                ],
                [
                    'branch_id' => $branches->get(($number - 1) % $branches->count()),
                    'service_id' => $services->get(($number - 1) % $services->count()),
                    'source_id' => $sources->get(($number - 1) % $sources->count()),
                    'category_id' => $categories->get(($number - 1) % $categories->count()),
                    'type_id' => $types->get(($number - 1) % $types->count()),
                    'priority_id' => $priorities->get(($number - 1) % $priorities->count()),
                    'status_id' => $statuses->get(($number - 1) % $statuses->count()),
                    'description' => "Demo complaint details for customer {$number}.",
                    'complaint_date' => now()->subDays($number % 30)->toDateString(),
                    'created_by' => $user->id,
                ],
            );
        }
    }
}
