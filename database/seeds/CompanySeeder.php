<?php

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company = Company::firstOrNew(['siret' => 'test_users']);
        if (!$company->exists) {
            $company->fill([
                'name' => 'Utilisateur de test',
                'siret' => 'test_users',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ])->save();
        }
    }
}
