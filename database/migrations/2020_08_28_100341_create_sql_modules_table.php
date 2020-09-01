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
            $table->enum('sql_type', ['mysql', 'sqlite', 'pgsql', 'sqlsrv'])->nullable();
            $table->string('host')->nullable();
            $table->string('port')->nullable();
            $table->string('database')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
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
