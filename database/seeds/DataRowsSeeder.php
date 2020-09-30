<?php

use App\Models\Customers;
use App\Models\DataRow;
use App\Models\DataType;
use App\Models\Workarea;
use App\User;
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
        /* #region Users */
        $type = $this->dataType('users');
        $row = $this->dataRow($type, 'id');
        $row->fill([
            'type'         => 'integer',
            'display_name' => 'Identifiant',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'firstname');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Prénom',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'lastname');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Nom',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'login');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Identifiant de connexion',
            'required'     => 1,
            'details' => json_encode([
                'is_unique' => 1,
                'remove_special_chars' => 1
            ])
        ])->save();

        $row = $this->dataRow($type, 'email');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Émail',
            'required'     => 0,
            'details' => json_encode([
                'is_unique' => 1
            ])
        ])->save();

        $row = $this->dataRow($type, 'password');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Mot de passe',
            'required'     => 1,
            'details' => json_encode([
                'is_password' => 1
            ])
        ])->save();

        $row = $this->dataRow($type, 'updated_at');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Dernière modification',
            'required'     => 0,
        ])->save();
        /* #endregion */

        /* #region Customers */
        $type = $this->dataType('customers');
        $row = $this->dataRow($type, 'id');
        $row->fill([
            'type'         => 'integer',
            'display_name' => 'Identifiant',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'name');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Nom de la société',
            'required'     => 0,
        ])->save();

        $row = $this->dataRow($type, 'lastname');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Nom du client',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'siret');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Siret',
            'required'     => 0,
        ])->save();

        $row = $this->dataRow($type, 'professional');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Professionnel',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'updated_at');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Dernière modification',
            'required'     => 0,
        ])->save();
        /* #endregion */

        /* #region Ranges */
        $type = $this->dataType('ranges');
        $row = $this->dataRow($type, 'id');
        $row->fill([
            'type'         => 'integer',
            'display_name' => 'Identifiant',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'name');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Nom',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'description');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Description',
            'required'     => 1,
            'details' => json_encode([
                'max_length' => 1500
            ])
        ])->save();

        $row = $this->dataRow($type, 'updated_at');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Dernière modification',
            'required'     => 0,
        ])->save();
        /* #endregion */

        /* #region Skills */
        $type = $this->dataType('skills');
        $row = $this->dataRow($type, 'id');
        $row->fill([
            'type'         => 'integer',
            'display_name' => 'Identifiant',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'name');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Nom',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'updated_at');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Dernière modification',
            'required'     => 0,
        ])->save();
        /* #endregion */

        /* #region Workareas */
        $type = $this->dataType('workareas');
        $row = $this->dataRow($type, 'id');
        $row->fill([
            'type'         => 'integer',
            'display_name' => 'Identifiant',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'name');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Nom',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'updated_at');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Dernière modification',
            'required'     => 0,
        ])->save();
        /* #endregion */

        /* #region Projects */
        $type = $this->dataType('projects');
        $row = $this->dataRow($type, 'id');
        $row->fill([
            'type'         => 'integer',
            'display_name' => 'Identifiant',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'name');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Nom',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'date');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Date de livraison',
            'required'     => 0,
        ])->save();

        $row = $this->dataRow($type, 'status');
        $row->fill([
            'type'         => 'enum',
            'display_name' => 'État',
            'required'     => 1,
            'details' => json_encode([
                'options' => ['todo', 'doing', 'done']
            ])
        ])->save();

        $row = $this->dataRow($type, 'customer_id');
        $row->fill([
            'type'         => 'relationship',
            'display_name' => 'Client',
            'required'     => 0,
            'details' => json_encode([
                'model' => Customers::class,
                'label' => 'lastname'
            ])
        ])->save();

        $row = $this->dataRow($type, 'updated_at');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Dernière modification',
            'required'     => 0,
        ])->save();
        /* #endregion */

        /* #region Unavailabilities */
        $type = $this->dataType('unavailabilities');
        $row = $this->dataRow($type, 'id');
        $row->fill([
            'type'         => 'integer',
            'display_name' => 'Identifiant',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'user_id');
        $row->fill([
            'type'         => 'relationship',
            'display_name' => 'Utilisateur',
            'required'     => 1,
            'details' => json_encode([
                'model' => User::class,
                'label' => 'email'
            ])
        ])->save();

        $row = $this->dataRow($type, 'reason');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Motif',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'starts_at');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Début',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'ends_at');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Fin',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'updated_at');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Dernière modification',
            'required'     => 0,
        ])->save();
        /* #endregion */

        /* #region Tasks */
        $type = $this->dataType('tasks');
        $row = $this->dataRow($type, 'id');
        $row->fill([
            'type'         => 'integer',
            'display_name' => 'Identifiant',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'user_id');
        $row->fill([
            'type'         => 'relationship',
            'display_name' => 'Utilisateur',
            'required'     => 1,
            'details' => json_encode([
                'model' => User::class,
                'label' => 'email'
            ])
        ])->save();

        $row = $this->dataRow($type, 'name');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Nom',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'description');
        $row->fill([
            'type'         => 'string',
            'display_name' => 'Description',
            'required'     => 1,
            'details' => json_encode([
                'max_length' => 1500
            ])
        ])->save();

        $row = $this->dataRow($type, 'date');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Date de livraison',
            'required'     => 0,
        ])->save();

        $row = $this->dataRow($type, 'order');
        $row->fill([
            'type'         => 'integer',
            'display_name' => 'Ordre',
            'required'     => 0,
        ])->save();

        $row = $this->dataRow($type, 'estimated_time');
        $row->fill([
            'type'         => 'integer',
            'display_name' => 'Temps éstimé',
            'required'     => 1,
        ])->save();

        $row = $this->dataRow($type, 'time_spent');
        $row->fill([
            'type'         => 'integer',
            'display_name' => 'Temps effectué',
            'required'     => 0,
        ])->save();

        $row = $this->dataRow($type, 'workarea_id');
        $row->fill([
            'type'         => 'relationship',
            'display_name' => 'Îlot',
            'required'     => 0,
            'details' => json_encode([
                'model' => Workarea::class,
                'label' => 'name'
            ])
        ])->save();

        $row = $this->dataRow($type, 'status');
        $row->fill([
            'type'         => 'enum',
            'display_name' => 'État',
            'required'     => 1,
            'details' => json_encode([
                'options' => ['todo', 'doing', 'done']
            ])
        ])->save();

        $row = $this->dataRow($type, 'updated_at');
        $row->fill([
            'type'         => 'datetime',
            'display_name' => 'Dernière modification',
            'required'     => 0,
        ])->save();
        /* #endregion */
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
