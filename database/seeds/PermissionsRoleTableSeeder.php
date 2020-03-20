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

        $keys = [
            'users',
            'roles',
            'permissions',
            'companies'
        ];
        // create permissions
        foreach ($keys as $key) {
            Permission::firstOrCreate(['name' => 'edit '.$key]);
            Permission::firstOrCreate(['name' => 'delete '.$key]);
            Permission::firstOrCreate(['name' => 'publish '.$key]);
    
        }
        
        $keys = [
            'superAdmin',
            'littleAdmin',
            'clientAdmin'
        ];

        foreach ($keys as $key) {
            Role::firstOrCreate(['name' => $key]);
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
