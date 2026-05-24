<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::values() as $name) {
            PermissionModel::findOrCreate($name, 'web');
        }

        $superAdmin = Role::findOrCreate(RoleName::SuperAdmin->value, 'web');
        $superAdmin->syncPermissions(PermissionModel::all());

        $admin = Role::findOrCreate(RoleName::Admin->value, 'web');
        $admin->syncPermissions($this->permissionValues(Permission::forAdmin()));

        $pro = Role::findOrCreate(RoleName::Pro->value, 'web');
        $pro->syncPermissions($this->permissionValues([
            ...Permission::forMember(),
            ...Permission::forPro(),
        ]));

        $member = Role::findOrCreate(RoleName::Member->value, 'web');
        $member->syncPermissions($this->permissionValues(Permission::forMember()));
    }

    /**
     * @param  list<Permission>  $permissions
     * @return list<string>
     */
    private function permissionValues(array $permissions): array
    {
        return array_map(fn (Permission $p) => $p->value, $permissions);
    }
}
