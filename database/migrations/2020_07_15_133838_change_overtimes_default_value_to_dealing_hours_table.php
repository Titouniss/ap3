<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOvertimesDefaultValueToDealingHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealing_hours', function (Blueprint $table) {
            $table->float('overtimes')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dealing_hours', function (Blueprint $table) {
            $table->float('overtimes')->default(-7)->change();
        });
    }
}
