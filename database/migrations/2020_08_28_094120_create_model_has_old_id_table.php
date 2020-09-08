<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelHasOldIdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('model_has_old_id', function (Blueprint $table) {
            $table->string('old_id');
            $table->unsignedBigInteger('new_id');
            $table->string('model');
            $table->primary(array('old_id', 'new_id', 'model'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('model_has_old_id');
    }
}
