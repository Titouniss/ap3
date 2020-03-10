<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRepetitiveTasksSkillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('repetitive_tasks_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('repetitive_task_id');
            $table->foreign('repetitive_task_id')->references('id')->on('repetitive_tasks')->onDelete('cascade');
            $table->unsignedBigInteger('skill_id');
            $table->foreign('skill_id')->references('id')->on('skills');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('repetitive_tasks_skills');
    }
}
