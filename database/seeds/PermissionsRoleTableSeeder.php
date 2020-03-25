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

        // [name, name_fr, isPublic]
        $Permkeys = [
            ['users', 'utilisateurs',true],
            ['roles', 'roles',true],

            ['permissions', 'permissions',false],
            ['companies', 'entreprises',false],
            ['workareas', 'îlots',true],
            ['skills', 'compétences',true],
            ['projects', 'projets',true]
        ];
        // create permissions
        foreach ($Permkeys as $Permkey) {
            Permission::firstOrCreate(['name' => 'read '.$Permkey[0], 'name_fr' => $Permkey[1], 'isPublic' => $Permkey[2]]);
            Permission::firstOrCreate(['name' => 'edit '.$Permkey[0], 'name_fr' => $Permkey[1], 'isPublic' => $Permkey[2]]);
            Permission::firstOrCreate(['name' => 'delete '.$Permkey[0], 'name_fr' => $Permkey[1], 'isPublic' => $Permkey[2]]);
            Permission::firstOrCreate(['name' => 'publish '.$Permkey[0], 'name_fr' => $Permkey[1], 'isPublic' => $Permkey[2]]);
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
                foreach ($Permkeys as $PermkeyArray) {
                    $Permkey = $PermkeyArray[0]; //get name only
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
