<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEstimatedTimeAndTimeSpentFormatToTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['estimated_time', 'time_spent']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('estimated_time')->after('date');
            $table->integer('time_spent')->nullable()->after('estimated_time');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['estimated_time', 'time_spent']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('estimated_time')->after('date');  // Estimation temps de la tâche
            $table->timestamp('time_spent')->after('estimated_time');;  // temps réellement passé
        });
    }
}
