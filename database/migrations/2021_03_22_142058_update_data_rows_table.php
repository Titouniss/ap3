<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateDataRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE data_rows MODIFY COLUMN type ENUM('string', 'integer', 'double', 'boolean', 'datetime', 'enum', 'relationship')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE data_rows MODIFY COLUMN type ENUM('string', 'integer', 'boolean', 'datetime', 'enum', 'relationship')");
    }
}
