<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDateToDescriptionToRepetitiveTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('repetitive_tasks', function (Blueprint $table) {
            $table->dropColumn('date');
            $table->dropColumn('estimated_time');
            $table->string('description',1500)->nullable()->after('order');
            $table->unsignedBigInteger('workarea_id')->nullable();
        });

        Schema::table('repetitive_tasks', function (Blueprint $table) {
            $table->integer('estimated_time')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('repetitive_tasks', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('estimated_time');
            $table->dropColumn('workarea_id');
            $table->timestamp('date')->nullable()->after('order');
        });

        Schema::table('repetitive_tasks', function (Blueprint $table) {
            $table->timestamp('estimated_time')->after('order');
        });
    }
}
