<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnDeleteCascadeToTaskTimeSpent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task_time_spent', function (Blueprint $table) {
            $table->dropForeign('task_time_spent_user_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->dropForeign('task_time_spent_task_id_foreign');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task_time_spent', function (Blueprint $table) {
            $table->dropForeign('task_time_spent_user_id_foreign');
            $table->foreign('user_id')->references('id')->on('users');
            $table->dropForeign('task_time_spent_task_id_foreign');
            $table->foreign('task_id')->references('id')->on('tasks');
        });
    }
}
