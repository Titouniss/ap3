<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSqlModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sql_modules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('sql_type', ['mysql', 'sqlite', 'pgsql', 'sqlsrv']);
            $table->string('host');
            $table->string('port');
            $table->string('database');
            $table->string('user');
            $table->string('password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sql_modules');
    }
}
