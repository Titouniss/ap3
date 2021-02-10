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
        $package = $this->package('hours');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Heures',
            ])->save();
        }
        $package->syncPermissions(Permission::whereIn('name_fr', ['entreprises', 'roles', 'utilisateurs', 'projets', 'tâches', 'clients', 'heures', 'heures_supplémentaires', 'indiponibilités'])
            ->orWhereIn('name', ['read skills', 'read ranges', 'read workareas', 'read schedules'])->get());

        $package = $this->package('schedules');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Plannings',
            ])->save();
        }
        $package->syncPermissions(Permission::whereIn('name_fr', ['entreprises', 'roles', 'utilisateurs', 'projets', 'clients', 'pôles_de_productions', 'tâches', 'compétences', 'planning', 'indiponibilités', 'gammes'])
            ->orWhereIn('name', ['read permissions'])->get());
    }

    private function package($name)
    {
        return Package::firstOrNew(['name' => $name, 'guard_name' => 'web']);
    }
}
