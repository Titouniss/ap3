<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamp('date')->nullable();  // Date de la tâche
            $table->timestamp('estimated_time');  // Estimation temps de la tâche
            $table->timestamp('time_spent');  // temps réellement passé
            $table->unsignedBigInteger('tasks_bundle_id');
            $table->foreign('tasks_bundle_id')->references('id')->on('tasks_bundles')->onDelete('cascade');       
            $table->unsignedBigInteger('workarea_id')->nullable(); // Zone de travail
            $table->foreign('workarea_id')->references('id')->on('workareas');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->enum('status', ['todo', 'doing', 'done']);
            $table->softDeletes();
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
        Schema::dropIfExists('tasks');
    }
}
