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
        $modules = ['customer','complaint','branch','service','source','category','type','priority','status','report','user','role','audit'];
        $actions = ['view','create','update','delete'];
        $permissions = [];
        foreach ($modules as $module) foreach ($actions as $action) $permissions[] = "$module.$action";
        $permissions[] = 'complaint.view_logs'; $permissions[] = 'complaint.export'; $permissions[] = 'report.export'; $permissions[] = 'audit.view';
        foreach (array_unique($permissions) as $permission) Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $all = Permission::all();
        $super = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']); $super->syncPermissions($all);
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']); $admin->syncPermissions($all->reject(fn ($p) => in_array($p->name, ['role.delete','user.delete','audit.view'], true)));
        $support = Role::firstOrCreate(['name' => 'Customer Support', 'guard_name' => 'web']); $support->syncPermissions(Permission::whereIn('name', ['customer.view','customer.create','customer.update','complaint.view','complaint.create','complaint.update'])->get());
        $viewer = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']); $viewer->syncPermissions(Permission::whereIn('name', ['customer.view','complaint.view','branch.view','service.view','source.view','category.view','type.view','priority.view','status.view','report.view'])->get());
        $superUser = User::updateOrCreate(['email' => 'admin@example.com'], ['name' => 'Super Admin', 'password' => 'password']); $superUser->syncRoles([$super]);
    }
}
