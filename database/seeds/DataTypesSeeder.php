<?php

use App\Models\DataType;
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
                'model' => 'App\User',
                'order' => 1
            ])->save();
        }

        $type = $this->dataType('customers');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Client',
                'display_name_plurial' => 'Clients',
                'model' => 'App\Models\Customers',
                'order' => 1
            ])->save();
        }

        $type = $this->dataType('ranges');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Gamme',
                'display_name_plurial' => 'Gammes',
                'model' => 'App\Models\Range',
                'order' => 1
            ])->save();
        }

        $type = $this->dataType('skills');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Compétence',
                'display_name_plurial' => 'Compétences',
                'model' => 'App\Models\Skill',
                'order' => 1
            ])->save();
        }

        $type = $this->dataType('workareas');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Îlot',
                'display_name_plurial' => 'Îlots',
                'model' => 'App\Models\Workarea',
                'order' => 1
            ])->save();
        }

        $type = $this->dataType('projects');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Projet',
                'display_name_plurial' => 'Projets',
                'model' => 'App\Models\Project',
                'order' => 2
            ])->save();
        }

        $type = $this->dataType('unavailabilities');
        if (!$type->exists) {
            $type->fill([
                'display_name_singular' => 'Indisponibilité',
                'display_name_plurial' => 'Indisponibilités',
                'model' => 'App\Models\Unavailability',
                'order' => 2
            ])->save();
        }
    }

    private function dataType($slug)
    {
        return DataType::firstOrNew(['slug' => $slug]);
    }
}
