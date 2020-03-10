<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $keys = [
            'superAdmin',
            'littleAdmin',
            'clientAdmin'
        ];

        foreach ($keys as $key) {
            Role::firstOrCreate(['name' => $key, 'guard_name' => $key,]);
        }
    }

}
