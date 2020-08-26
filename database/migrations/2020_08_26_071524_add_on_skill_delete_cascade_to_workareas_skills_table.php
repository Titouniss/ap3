<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnSkillDeleteCascadeToWorkareasSkillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workareas_skills', function (Blueprint $table) {
            $table->dropForeign('workareas_skills_skill_id_foreign');
            $table->foreign('skill_id')->references('id')->on('skills')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workareas_skills', function (Blueprint $table) {
            $table->dropForeign('workareas_skills_skill_id_foreign');
            $table->foreign('skill_id')->references('id')->on('skills');
        });
    }
}
