<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreateByToTasksCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->after('task_id');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->dropForeign('task_comments_created_by_foreign');
            $table->dropColumn(['created_by']);
        });
    }
}
