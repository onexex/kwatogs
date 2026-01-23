<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emp_infos', function (Blueprint $table) {
            $table->id();
            $table->char('empID',20)->nullable();
            $table->date('empBdate')->nullable();
            $table->char('empCStatus',20)->nullable();
            $table->char('empReligion',20)->nullable();
            $table->char('empPContact',20)->nullable();
            $table->char('empHContact',20)->nullable();
            $table->char('empEmail',20)->nullable();
            $table->char('empAddStreet',50)->nullable();
            $table->char('empAddCityDesc',50)->nullable();
            $table->char('empAddCity',50)->nullable();
            $table->char('empAddBrgyDesc',50)->nullable();
            $table->char('empAddBrgy',50)->nullable();
            $table->char('empProvDesc',50)->nullable();
            $table->char('empProv',50)->nullable();
            $table->char('empZipcode',50)->nullable();
            $table->char('empCountry',50)->default('Philippine');
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
        Schema::dropIfExists('emp_infos');
    }
};
