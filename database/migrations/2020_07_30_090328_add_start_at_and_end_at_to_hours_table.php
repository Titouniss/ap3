<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStartAtAndEndAtToHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hours', function (Blueprint $table) {
            $table->dropColumn(['date', 'duration']);
        });

        Schema::table('hours', function (Blueprint $table) {
            $table->datetime('start_at')->after('description');
            $table->datetime('end_at')->after('start_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hours', function (Blueprint $table) {
            $table->dropColumn(['start_at', 'end_at']);
        });

        Schema::table('hours', function (Blueprint $table) {
            $table->date('date');
            $table->time('duration');
        });
    }
}
