<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_rows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('data_type_id');
            $table->foreign('data_type_id')->references('id')->on('data_types')->onDelete('cascade');
            $table->string('display_name');
            $table->string('field');
            $table->boolean('required')->default(false);
            $table->enum('type', ['string', 'integer', 'boolean', 'datetime', 'enum', 'relationship']);
            $table->string('details')->nullable();
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
        Schema::dropIfExists('data_rows');
    }
}
