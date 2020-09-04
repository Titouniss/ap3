<?php

use App\Models\DataRow;
use App\Models\DataType;
use Illuminate\Database\Seeder;

class DataRowsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /***************** Users *****************/
        $type = $this->dataType('users');
        $row = $this->dataRow($type, 'id');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'integer',
                'display_name' => 'Identifiant',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'firstname');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Prénom',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'lastname');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Nom',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'email');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Émail',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'password');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Mot de passe',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'updated_at');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'datetime',
                'display_name' => 'Dernière modification',
                'required'     => 0,
            ])->save();
        }

        /***************** Customers *****************/
        $type = $this->dataType('customers');
        $row = $this->dataRow($type, 'id');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'integer',
                'display_name' => 'Identifiant',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'name');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Nom de la société',
                'required'     => 0,
            ])->save();
        }
        $row = $this->dataRow($type, 'lastname');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Nom du client',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'siret');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Siret',
                'required'     => 0,
            ])->save();
        }
        $row = $this->dataRow($type, 'professional');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Professionnel',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'updated_at');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'datetime',
                'display_name' => 'Dernière modification',
                'required'     => 0,
            ])->save();
        }

        /***************** Ranges *****************/
        $type = $this->dataType('ranges');
        $row = $this->dataRow($type, 'id');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'integer',
                'display_name' => 'Identifiant',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'name');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Nom',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'description');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Description',
                'required'     => 1,
                'details' => json_encode([
                    'max_length' => 1500
                ])
            ])->save();
        }
        $row = $this->dataRow($type, 'updated_at');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'datetime',
                'display_name' => 'Dernière modification',
                'required'     => 0,
            ])->save();
        }

        /***************** Skills *****************/
        $type = $this->dataType('skills');
        $row = $this->dataRow($type, 'id');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'integer',
                'display_name' => 'Identifiant',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'name');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Nom',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'updated_at');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'datetime',
                'display_name' => 'Dernière modification',
                'required'     => 0,
            ])->save();
        }

        /***************** Workareas *****************/
        $type = $this->dataType('workareas');
        $row = $this->dataRow($type, 'id');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'integer',
                'display_name' => 'Identifiant',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'name');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Nom',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'updated_at');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'datetime',
                'display_name' => 'Dernière modification',
                'required'     => 0,
            ])->save();
        }

        /***************** Projects *****************/
        $type = $this->dataType('projects');
        $row = $this->dataRow($type, 'id');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'integer',
                'display_name' => 'Identifiant',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'name');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Nom',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'date');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'datetime',
                'display_name' => 'Date de livraison',
                'required'     => 0,
            ])->save();
        }
        $row = $this->dataRow($type, 'status');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'enum',
                'display_name' => 'État',
                'required'     => 1,
                'details' => json_encode([
                    'options' => ['todo', 'doing', 'done']
                ])
            ])->save();
        }
        $row = $this->dataRow($type, 'customer_id');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'relationship',
                'display_name' => 'Client',
                'required'     => 0,
                'details' => json_encode([
                    'model' => 'App\Models\Customers',
                    'label' => 'lastname'
                ])
            ])->save();
        }
        $row = $this->dataRow($type, 'updated_at');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'datetime',
                'display_name' => 'Dernière modification',
                'required'     => 0,
            ])->save();
        }

        /***************** Unavailabilities *****************/
        $type = $this->dataType('unavailabilities');
        $row = $this->dataRow($type, 'id');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'integer',
                'display_name' => 'Identifiant',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'user_id');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'relationship',
                'display_name' => 'Utilisateur',
                'required'     => 1,
                'details' => json_encode([
                    'model' => 'App\User',
                    'label' => 'email'
                ])
            ])->save();
        }
        $row = $this->dataRow($type, 'reason');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'string',
                'display_name' => 'Motif',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'starts_at');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'datetime',
                'display_name' => 'Début',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'ends_at');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'datetime',
                'display_name' => 'Fin',
                'required'     => 1,
            ])->save();
        }
        $row = $this->dataRow($type, 'updated_at');
        if (!$row->exists) {
            $row->fill([
                'type'         => 'datetime',
                'display_name' => 'Dernière modification',
                'required'     => 0,
            ])->save();
        }
    }

    private function dataRow($type, $field)
    {
        return DataRow::firstOrNew([
            'data_type_id' => $type->id,
            'field'        => $field,
        ]);
    }

    private function dataType($slug)
    {
        return DataType::where('slug', $slug)->firstOrFail();
    }
}
