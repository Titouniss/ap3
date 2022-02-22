<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('code')->default("")->after('siret');
            $table->string('type')->default("")->after('code');
            $table->string('contact_firstname')->default("")->after('type');
            $table->string('contact_lastname')->default("")->after('contact_firstname');
            $table->string('contact_tel1')->default("")->after('contact_lastname');
            $table->string('contact_tel2')->default("")->after('contact_tel1');
            $table->string('contact_email')->default("")->after('contact_tel2');
            $table->string('street_number')->default("")->after('contact_email');
            $table->string('street_name')->default("")->after('street_number');
            $table->string('postal_code')->default("")->after('street_name');
            $table->string('city')->default("")->after('postal_code');
            $table->string('country')->default("")->after('city');
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
            $table->dropColumn('code');
            $table->dropColumn('type');
            $table->dropColumn('contact_firstname');
            $table->dropColumn('contact_lastname');
            $table->dropColumn('contact_tel1');
            $table->dropColumn('contact_tel2');
            $table->dropColumn('contact_email');
            $table->dropColumn('street_number');
            $table->dropColumn('street_name');
            $table->dropColumn('postal_code');
            $table->dropColumn('city');
            $table->dropColumn('country');
        });
    }
}
