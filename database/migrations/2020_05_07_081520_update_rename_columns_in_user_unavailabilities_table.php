<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRenameColumnsInUserUnavailabilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_unavailabilities', function (Blueprint $table) {
            $table->renameColumn('start_at', 'starts_at');
            $table->renameColumn('end_at', 'ends_at');
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
            $table->renameColumn('starts_at', 'start_at');
            $table->renameColumn('ends_at', 'end_at');
        });
    }
}
