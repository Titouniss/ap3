<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\User;
use Illuminate\Support\Facades\DB;

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

        // [name, name_fr, is_public]
        $Permkeys = [
            ['users', 'utilisateurs', true],
            ['roles', 'roles', true],

            ['permissions', 'permissions', false],
            ['companies', 'entreprises', false],
            ['workareas', 'pôles_de_productions', true],
            ['skills', 'compétences', true],
            ['projects', 'projets', true],
            ['tasks', 'tâches', true],
            ['ranges', 'gammes', true],
            ['hours', 'heures', true],
            ['unavailabilities', 'indiponibilités', true],
            ['schedules', 'planning', true],
            ['dealingHours', 'heures_supplémentaires', true],
            ['customers', 'clients', true],
            ['modules', 'modules', false],
            ['subscriptions', 'abonnements', false],
            ['bugs', 'bugs', false],
            ['doc', 'documents', true],
            ['todos', 'todos', true],
            ['tags', 'tags', true],

        ];
        // create permissions
        foreach ($Permkeys as $Permkey) {
            Permission::firstOrCreate(['name' => 'read ' . $Permkey[0], 'name_fr' => $Permkey[1], 'is_public' => $Permkey[2]]);
            Permission::firstOrCreate(['name' => 'edit ' . $Permkey[0], 'name_fr' => $Permkey[1], 'is_public' => $Permkey[2]]);
            Permission::firstOrCreate(['name' => 'delete ' . $Permkey[0], 'name_fr' => $Permkey[1], 'is_public' => $Permkey[2]]);
            Permission::firstOrCreate(['name' => 'publish ' . $Permkey[0], 'name_fr' => $Permkey[1], 'is_public' => $Permkey[2]]);
            Permission::firstOrCreate(['name' => 'show ' . $Permkey[0], 'name_fr' => $Permkey[1], 'is_public' => $Permkey[2]]);
        }

        $Rolekeys = [
            ['superAdmin', false],
            ['Administrateur', true], // role publique
            ['Utilisateur', true] // role publique
        ];

        foreach ($Rolekeys as $roleKey) {
            $role = Role::firstOrCreate(['name' => $roleKey[0], 'is_public' => $roleKey[1]]);
            foreach ($Permkeys as $PermkeyArray) {
                $Permkey = $PermkeyArray[0];
                $role->revokePermissionTo(['read ' . $Permkey, 'edit ' . $Permkey, 'delete ' . $Permkey, 'publish ' . $Permkey, 'show ' . $Permkey]);
            }
        }

        $role = Role::firstOrCreate(['code' => 'super_admin', 'name' => 'superAdmin']);
        $role->givePermissionTo(Permission::all());
        $role->code = 'super_admin';
        $role->save();

        $role = Role::firstOrCreate(['code' => 'admin', 'name' => 'Administrateur']);
        $role->givePermissionTo(Permission::all()->filter(function ($perm) {
            return !in_array($perm->name_fr, ['permissions', 'modules', 'abonnements']);
        }));
        $role->givePermissionTo('read permissions');
        $role->code = 'admin';
        $role->save();

        $role = Role::firstOrCreate(['code' => 'user', 'name' => 'Utilisateur']);
        // Give all permissions with name_fr
        $role->givePermissionTo(Permission::whereIn('name_fr', ['heures', 'planning', 'tâches', 'indiponibilités', 'heures_supplémentaires', 'bugs', 'documents','todos','tags'])->get());
        // Give specific permission by name
        $role->givePermissionTo(Permission::whereIn('name', ['read companies', 'read customers', 'read projects', 'read permissions', 'read ranges', 'read skills', 'read users', 'read workareas'])->get());
        $role->code = 'user';
        $role->save();

        $admin = User::where('email', 'admin@numidev.fr')->first();
        if ($admin == null) {
            $admin = User::create([
                'firstname' => 'admin',
                'lastname' => 'NUMIDEV',
                'login' => 'admin',
                'email' => 'admin@numidev.fr',
                'password' => Hash::make('password'),
                //'email_verified_at' => '2020-01-01 00:00:00.000000',
                'isTermsConditionAccepted' => true,
            ]);
            $admin->syncRoles('superAdmin');
        } else {
            $admin->syncRoles('superAdmin');
        }
    }
}
