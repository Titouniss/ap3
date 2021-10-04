<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;

class SetUsersLoginsToUsersTable extends Migration
{
    use SoftDeletes;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = DB::table('users')->get();

        foreach ($users as $key => $user) {
            if($user->email == "admin@numidev.fr") {
                DB::table('users')->where('email', "admin@numidev.fr")->update(['login' => 'numidev.adminNumidev']);
            } else {
                $company = DB::table('companies')->where('id', $user->company_id)->first();
                $company_name = $company->name;
                $company_name = preg_replace("/ /", "_", $company_name);

                $login_temp = mb_strtolower($company_name, 'UTF-8') . "." . mb_strtolower($user->firstname, 'UTF-8') . ucfirst(mb_strtolower($user->lastname, 'UTF-8'));
                $parsed_login = static::str_to_noaccent($login_temp);

                $login = $parsed_login;

                if(User::where('login', $login)->withTrashed()->exists()) {
                    do {
                        $login = $parsed_login;
                        $login = $parsed_login . rand(0, 9999);
                    } while (User::where('login', $login)->withTrashed()->exists());
                }
                DB::table('users')->where('id', $user->id)->update(['login' => $login]);
            }
        }
        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->nullable(false)->change();
        });
    }

    public static function str_to_noaccent($str)
    {
        $parsed = $str;
        $parsed = preg_replace('#Ç#', 'C', $parsed);
        $parsed = preg_replace('#ç#', 'c', $parsed);
        $parsed = preg_replace('#è|é|ê|ë#', 'e', $parsed);
        $parsed = preg_replace('#È|É|Ê|Ë#', 'E', $parsed);
        $parsed = preg_replace('#à|á|â|ã|ä|å#', 'a', $parsed);
        $parsed = preg_replace('#@|À|Á|Â|Ã|Ä|Å#', 'A', $parsed);
        $parsed = preg_replace('#ì|í|î|ï#', 'i', $parsed);
        $parsed = preg_replace('#Ì|Í|Î|Ï#', 'I', $parsed);
        $parsed = preg_replace('#ð|ò|ó|ô|õ|ö#', 'o', $parsed);
        $parsed = preg_replace('#Ò|Ó|Ô|Õ|Ö#', 'O', $parsed);
        $parsed = preg_replace('#ù|ú|û|ü#', 'u', $parsed);
        $parsed = preg_replace('#Ù|Ú|Û|Ü#', 'U', $parsed);
        $parsed = preg_replace('#ý|ÿ#', 'y', $parsed);
        $parsed = preg_replace('#Ý#', 'Y', $parsed);
        $parsed = preg_replace('/\s/', '_', $parsed);

        return ($parsed);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->nullable(true)->change();
        });

        $users = DB::table('users')->get();

        foreach ($users as $key => $user) {
            DB::table('users')->where('id', $user->id)->update(['login' => null]);
        }
    }
}
