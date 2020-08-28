<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CompanySeeder::class);
        $this->call(PermissionsRoleTableSeeder::class);
        $this->call(DataTypesSeeder::class);
        $this->call(DataRowsSeeder::class);
    }
}
