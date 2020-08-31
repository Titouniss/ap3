<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModuleDataRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('module_data_rows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('module_data_type_id');
            $table->foreign('module_data_type_id')->references('id')->on('module_data_types');
            $table->unsignedBigInteger('data_row_id');
            $table->foreign('data_row_id')->references('id')->on('data_rows');
            $table->string('source');
            $table->string('default_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('module_data_rows');
    }
}
