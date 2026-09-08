<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Idempotently installs the explicit resolution/review permissions without
 * touching any user record (unlike PermissionSeeder, it never resets the
 * demo admin password), so it is safe to run against the live database:
 *
 *     php artisan db:seed --class=ResolutionWorkflowPermissionSeeder
 */
class ResolutionWorkflowPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'visit.resolution.submit',
            'visit.resolution.review',
            'visit.resolution.approve',
            'visit.resolution.reject',
        ];

        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $resolutions = Permission::whereIn('name', $names)->get();

        foreach (['Super Admin', 'Admin', 'Quality Manager'] as $roleName) {
            Role::where('name', $roleName)->first()?->givePermissionTo($resolutions);
        }

        Role::where('name', 'Customer Support')->first()?->givePermissionTo(
            Permission::where('name', 'visit.resolution.submit')->firstOrFail()
        );

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}