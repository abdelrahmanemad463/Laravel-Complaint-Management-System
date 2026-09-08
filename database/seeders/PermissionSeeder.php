<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = ['customer','complaint','branch','service','source','category','type','priority','status','report','user','role','audit','visit'];
        $actions = ['view','create','update','delete'];
        $permissions = [];
        foreach ($modules as $module) foreach ($actions as $action) $permissions[] = "$module.$action";
        $permissions[] = 'complaint.view_logs'; $permissions[] = 'complaint.export'; $permissions[] = 'report.export'; $permissions[] = 'audit.view';
        $permissions[] = 'visit.submit'; $permissions[] = 'visit.manage'; $permissions[] = 'visit.review';
        $permissions[] = 'visit.resolution.submit'; $permissions[] = 'visit.resolution.review'; $permissions[] = 'visit.resolution.approve'; $permissions[] = 'visit.resolution.reject';
        $permissions[] = 'visit.master.view'; $permissions[] = 'visit.master.import'; $permissions[] = 'visit.master.export';
        $permissions[] = 'dashboard.view'; $permissions[] = 'dashboard.visitors.view';
        foreach (array_unique($permissions) as $permission) Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $all = Permission::all();
        $super = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']); $super->syncPermissions($all);
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']); $admin->syncPermissions($all->reject(fn ($p) => in_array($p->name, ['role.delete','user.delete','audit.view'], true)));
        $qualityManager = Role::firstOrCreate(['name' => 'Quality Manager', 'guard_name' => 'web']); $qualityManager->syncPermissions(Permission::whereIn('name', ['visit.view','visit.create','visit.update','visit.submit','visit.resolution.submit','visit.resolution.review','visit.resolution.approve','visit.resolution.reject','visit.master.view','report.view','dashboard.view','dashboard.visitors.view'])->get());
        $support = Role::firstOrCreate(['name' => 'Customer Support', 'guard_name' => 'web']); $support->syncPermissions(Permission::whereIn('name', ['customer.view','customer.create','customer.update','complaint.view','complaint.create','complaint.update','visit.view','visit.create','visit.update','visit.submit','visit.resolution.submit','dashboard.view'])->get());
        $viewer = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']); $viewer->syncPermissions(Permission::whereIn('name', ['customer.view','complaint.view','branch.view','service.view','source.view','category.view','type.view','priority.view','status.view','report.view','visit.view','dashboard.view','dashboard.visitors.view'])->get());
        $superUser = User::updateOrCreate(['email' => 'admin@example.com'], ['name' => 'Super Admin', 'password' => 'password']); $superUser->syncRoles([$super]);
    }
}
