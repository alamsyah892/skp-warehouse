<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PERMISSION
        // Daftar entity yang punya CRUD
        $entities = [
            'User',

            'Role',
            'Company',
            'Warehouse',
            'Division',
            'Project',
            'Item Category',
            'Item',
            'Vendor',
            'Courier',
            'Currency',
            'Bank',

            'Purchase Request',
        ];

        // Generate permissions
        $actions = ['Create', 'Read', 'Update', 'Delete'];
        $permissions = [];
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $name = "$action $entity";
                $permissions[$name] = Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web']
                );
            }
        }


        // ROLES & ROLE PERMISSIONS
        // Roles dengan full permissions
        $fullAccessRoles = [
            'Project Owner',
            'Administrator',
        ];

        foreach ($fullAccessRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }


        // Definisikan permission dalam bentuk mapping entity => actions
        $mappingActions = [
            'Company' => ['Read'],
            'Warehouse' => ['Read'],
            'Division' => ['Read'],
            'Project' => ['Read'],
            'Item Category' => ['Read'],
            'Item' => ['Read'],
            'Vendor' => ['Read'],
            'Courier' => ['Read'],
            'Currency' => ['Read'],
            'Bank' => ['Read'],

            'Purchase Request' => ['Read'],
        ];

        $syncPermissions = [];
        foreach ($mappingActions as $entity => $actions) {
            foreach ($actions as $action) {
                $syncPermissions[] = $permissions["$action $entity"];
            }
        }

        $limitedAccessRoles = [
            'Logistic',
            'Logistic Manager',
            'Quantity Surveyor',
            'Audit',
            'Audit Manager',
            'Purchasing',
            'Purchasing Manager',
            'Finance',
            'Finance Manager',
        ];

        foreach ($limitedAccessRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($syncPermissions);
        }


        // USERS
        $owner = User::firstOrCreate(
            [
                'name' => 'Owner',
                'email' => 'owner@app.com',
                'password' => '$2y$12$Sr48yj4G1oie5p21ttW5vuzWNfGitodynUyPZt0NDnIecBka.Fmg.',
                'email_verified_at' => now(),
                'avatar_url' => 'https://ui-avatars.com/api/?name=Owner&background=random&color=fff',
            ]
        );
        $owner->assignRole('Project Owner');

        $admin = User::firstOrCreate(
            [
                'name' => 'Administrator',
                'email' => 'admin@app.com',
                'password' => '$2y$12$Sr48yj4G1oie5p21ttW5vuzWNfGitodynUyPZt0NDnIecBka.Fmg.',
                'email_verified_at' => now(),
                'avatar_url' => 'https://ui-avatars.com/api/?name=Administrator&background=random&color=fff',
            ]
        );
        $admin->assignRole('Administrator');
    }
}
