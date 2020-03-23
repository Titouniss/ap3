<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrialEndsAtToCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['siret']);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('expire_at')->nullable();
            $table->string('siret')->unique()->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['expire_at', 'siret']);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->string('siret')->unique()->after('name');
        });
    }
}
