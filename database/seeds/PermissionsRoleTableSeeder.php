<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\User;

class PermissionsRoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $Permkeys = [
            'users',
            'roles',
            'permissions',
            'companies',
            'workareas',
            'skills',
            'projects'
        ];
        // create permissions
        foreach ($Permkeys as $Permkey) {
            Permission::firstOrCreate(['name' => 'read '.$Permkey]);
            Permission::firstOrCreate(['name' => 'edit '.$Permkey]);
            Permission::firstOrCreate(['name' => 'delete '.$Permkey]);
            Permission::firstOrCreate(['name' => 'publish '.$Permkey]);
        }
        
        $Rolekeys = [
            'superAdmin',
            'littleAdmin',
            'clientAdmin'
        ];

        foreach ($Rolekeys as $key) {
            $role = Role::firstOrCreate(['name' => $key]);
            if ($key == 'superAdmin') {
                $role->givePermissionTo(Permission::all());
            } else {
                foreach ($Permkeys as $Permkey) {
                    if ($Permkey == 'permissions' || $Permkey == 'companies') {
                        if ($key == 'littleAdmin') {
                            $role->givePermissionTo(['read '.$Permkey,'edit '.$Permkey, 'delete '.$Permkey, 'publish '.$Permkey]);
                        }
                    } else {
                        $role->givePermissionTo(['read '.$Permkey,'edit '.$Permkey, 'delete '.$Permkey, 'publish '.$Permkey]);
                    }
                }
            }

        }

        $admin = User::where('email', 'admin@numidev.fr')->first();
        if ($admin == null) {
            $admin = User::create([
                'firstname' => 'admin',
                'lastname' => 'NUMIDEV',
                'email' => 'admin@numidev.fr',
                'password' => Hash::make('password'),
                'isTermsConditionAccepted' => true
            ]);
            $admin->syncRoles('superAdmin');
        } else {
            $admin->syncRoles('superAdmin');
        }
    }
}
