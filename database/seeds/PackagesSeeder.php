<?php

use App\Models\Package;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PackagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $package = $this->package('workareas');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Pôles de production',
            ])->save();
            $package->givePermissionTo(Permission::where('name_fr', 'pôles_de_productions')->orWhereIn('name', ['read skills', 'read companies'])->get());
        }
        $package = $this->package('users');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Utilisateurs',
            ])->save();
            $package->givePermissionTo(Permission::where('name_fr', 'utilisateurs')->orWhereIn('name', ['read roles', 'read companies', 'read skills'])->get());
        }
        $package = $this->package('tasks');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Tâches',
            ])->save();
            $package->givePermissionTo(Permission::where('name_fr', 'tâches')->orWhereIn('name', ['read users'])->get());
        }
        $package = $this->package('skills');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Compétences',
            ])->save();
            $package->givePermissionTo(Permission::where('name_fr', 'compétences')->orWhereIn('name', ['read companies', 'read workareas'])->get());
        }
        $package = $this->package('schedules');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Plannings',
            ])->save();
            $package->givePermissionTo(Permission::where('name_fr', 'planning')->orWhereIn('name', ['read workareas', 'read skills', 'read users'])->get());
        }
        $package = $this->package('roles');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Rôles',
            ])->save();
            $package->givePermissionTo(Permission::where('name_fr', 'roles')->orWhereIn('name', ['read permissions', 'read users'])->get());
        }
        $package = $this->package('projects');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Projets',
            ])->save();
            $package->givePermissionTo(Permission::where('name_fr', 'projects')->orWhereIn('name', ['read companies', 'read ranges', 'read clients', 'read workareas'])->get());
        }
        $package = $this->package('hours');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Heures',
            ])->save();
            $package->givePermissionTo(Permission::where('name_fr', 'heures')->orWhereIn('name', ['read projects', 'read users', 'read schedules'])->get());
        }
        $package = $this->package('clients');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Clients',
            ])->save();
            $package->givePermissionTo(Permission::where('name_fr', 'heures')->orWhereIn('name', ['read companies'])->get());
        }
    }

    private function package($name)
    {
        return Package::firstOrNew(['name' => $name, 'guard_name' => 'web']);
    }
}
