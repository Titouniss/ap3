<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLoginAndEmailNullableToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $column = Schema::hasColumn('users', 'login');
            if (!$column) {
                $table->string('login')->nullable(true)->unique()->after('lastname');
            }
                $table->string('email')->nullable()->change();
            });
        }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $column = Schema::hasColumn('users', 'login');
            if ($column) {
                $table->dropColumn('login');
            }
            $table->string('email')->nullable(false)->change();
        });
    }
}
