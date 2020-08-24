<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUsedHoursFromDealingHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dealing_hours', function (Blueprint $table) {
            $table->dropColumn(['used_hours', 'used_type']);
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
            $table->integer('used_hours')->default(0);
            $table->string('used_type')->nullable();
        });
    }
}
