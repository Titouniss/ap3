<?php

use App\Models\Customers;
use App\Models\DataType;
use App\Models\Project;
use App\Models\Range;
use App\Models\Skill;
use App\Models\Task;
use App\Models\Unavailability;
use App\Models\Workarea;
use App\User;
use Illuminate\Database\Seeder;

class DataTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $type = $this->dataType('users');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Utilisateur',
                'display_name_plurial' => 'Utilisateurs',
                'model' => User::class,
                'order' => 1
            ])->save();
        }

        $type = $this->dataType('customers');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Client',
                'display_name_plurial' => 'Clients',
                'model' => Customers::class,
                'order' => 1
            ])->save();
        }

        $type = $this->dataType('ranges');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Gamme',
                'display_name_plurial' => 'Gammes',
                'model' => Range::class,
                'order' => 1
            ])->save();
        }

        $type = $this->dataType('skills');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Compétence',
                'display_name_plurial' => 'Compétences',
                'model' => Skill::class,
                'order' => 1
            ])->save();
        }

        $type = $this->dataType('workareas');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Îlot',
                'display_name_plurial' => 'Îlots',
                'model' => Workarea::class,
                'order' => 1
            ])->save();
        }

        $type = $this->dataType('projects');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Projet',
                'display_name_plurial' => 'Projets',
                'model' => Project::class,
                'order' => 2
            ])->save();
        }

        $type = $this->dataType('unavailabilities');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Indisponibilité',
                'display_name_plurial' => 'Indisponibilités',
                'model' => Unavailability::class,
                'order' => 2
            ])->save();
        }

        $type = $this->dataType('tasks');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Tâche',
                'display_name_plurial' => 'Tâches',
                'model' => Task::class,
                'order' => 3
            ])->save();
        }
    }

    private function dataType($slug)
    {
        return DataType::firstOrNew(['slug' => $slug]);
    }
}
