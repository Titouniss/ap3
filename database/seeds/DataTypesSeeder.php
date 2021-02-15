<?php

use App\Models\Customer;
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
        $type->fill([
            'display_name_singular' => 'Utilisateur',
            'display_name_plurial' => 'Utilisateurs',
            'model' => User::class,
            'order' => 1
        ])->save();

        $type = $this->dataType('customers');
        $type->fill([
            'display_name_singular' => 'Client',
            'display_name_plurial' => 'Clients',
            'model' => Customer::class,
            'order' => 1
        ])->save();

        $type = $this->dataType('ranges');
        $type->fill([
            'display_name_singular' => 'Gamme',
            'display_name_plurial' => 'Gammes',
            'model' => Range::class,
            'order' => 1
        ])->save();

        $type = $this->dataType('skills');
        $type->fill([
            'display_name_singular' => 'Compétence',
            'display_name_plurial' => 'Compétences',
            'model' => Skill::class,
            'order' => 1
        ])->save();

        $type = $this->dataType('workareas');
        $type->fill([
            'display_name_singular' => 'Pôle de produciton',
            'display_name_plurial' => 'Pôles de produciton',
            'model' => Workarea::class,
            'order' => 1
        ])->save();

        $type = $this->dataType('projects');
        $type->fill([
            'display_name_singular' => 'Projet',
            'display_name_plurial' => 'Projets',
            'model' => Project::class,
            'order' => 2
        ])->save();

        $type = $this->dataType('unavailabilities');
        $type->fill([
            'display_name_singular' => 'Indisponibilité',
            'display_name_plurial' => 'Indisponibilités',
            'model' => Unavailability::class,
            'order' => 2
        ])->save();

        $type = $this->dataType('tasks');
        $type->fill([
            'display_name_singular' => 'Tâche',
            'display_name_plurial' => 'Tâches',
            'model' => Task::class,
            'order' => 3
        ])->save();
    }

    private function dataType($slug)
    {
        return DataType::firstOrNew(['slug' => $slug]);
    }
}
