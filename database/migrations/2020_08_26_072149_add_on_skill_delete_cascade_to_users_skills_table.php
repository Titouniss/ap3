<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnSkillDeleteCascadeToUsersSkillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users_skills', function (Blueprint $table) {
            $table->dropForeign('users_skills_skill_id_foreign');
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
        Schema::table('users_skills', function (Blueprint $table) {
            $table->dropForeign('users_skills_skill_id_foreign');
            $table->foreign('skill_id')->references('id')->on('skills');
        });
    }
}
