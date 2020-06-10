<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameUserIndisponibilitiesToUserUnavailabilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_indisponibilities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::rename('user_indisponibilities', 'user_unavailabilities');

        Schema::table('user_unavailabilities', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_unavailabilities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::rename('user_unavailabilities', 'user_indisponibilities');

        Schema::table('user_indisponibilities', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
