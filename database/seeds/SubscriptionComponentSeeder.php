<?php

use App\Models\SubscriptionComponent;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SubscriptionComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $component = $this->component('workareas');
        if (!$component->exists) {
            $component->fill([
                'display_name' => 'Pôles de production',
            ])->save();
            $component->givePermissionTo(Permission::where('name_fr', 'pôles_de_productions')->orWhereIn('name', ['read skills', 'read companies'])->get());
        }
        $component = $this->component('users');
        if (!$component->exists) {
            $component->fill([
                'display_name' => 'Utilisateurs',
            ])->save();
            $component->givePermissionTo(Permission::where('name_fr', 'utilisateurs')->orWhereIn('name', ['read roles', 'read companies', 'read skills'])->get());
        }
        $component = $this->component('tasks');
        if (!$component->exists) {
            $component->fill([
                'display_name' => 'Tâches',
            ])->save();
            $component->givePermissionTo(Permission::where('name_fr', 'tâches')->orWhereIn('name', ['read users'])->get());
        }
        $component = $this->component('skills');
        if (!$component->exists) {
            $component->fill([
                'display_name' => 'Compétences',
            ])->save();
            $component->givePermissionTo(Permission::where('name_fr', 'compétences')->orWhereIn('name', ['read companies', 'read workareas'])->get());
        }
        $component = $this->component('schedules');
        if (!$component->exists) {
            $component->fill([
                'display_name' => 'Plannings',
            ])->save();
            $component->givePermissionTo(Permission::where('name_fr', 'planning')->orWhereIn('name', ['read workareas', 'read skills', 'read users'])->get());
        }
        $component = $this->component('roles');
        if (!$component->exists) {
            $component->fill([
                'display_name' => 'Rôles',
            ])->save();
            $component->givePermissionTo(Permission::where('name_fr', 'roles')->orWhereIn('name', ['read permissions', 'read users'])->get());
        }
        $component = $this->component('projects');
        if (!$component->exists) {
            $component->fill([
                'display_name' => 'Projets',
            ])->save();
            $component->givePermissionTo(Permission::where('name_fr', 'projects')->orWhereIn('name', ['read companies', 'read ranges', 'read clients', 'read workareas'])->get());
        }
        $component = $this->component('hours');
        if (!$component->exists) {
            $component->fill([
                'display_name' => 'Heures',
            ])->save();
            $component->givePermissionTo(Permission::where('name_fr', 'heures')->orWhereIn('name', ['read projects', 'read users', 'read schedules'])->get());
        }
        $component = $this->component('clients');
        if (!$component->exists) {
            $component->fill([
                'display_name' => 'Clients',
            ])->save();
            $component->givePermissionTo(Permission::where('name_fr', 'heures')->orWhereIn('name', ['read companies'])->get());
        }
    }

    private function component($name)
    {
        return SubscriptionComponent::firstOrNew(['name' => $name, 'guard_name' => 'web']);
    }
}
