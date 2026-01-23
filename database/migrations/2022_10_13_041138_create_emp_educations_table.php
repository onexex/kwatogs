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
        Schema::create('emp_educations', function (Blueprint $table) {
            $table->id();
            $table->char('empID',50)->nullable();
            $table->char('schoolLevel',50)->nullable();
            $table->char('schoolName',50)->nullable();
            $table->char('schoolYearStarted',50)->nullable();
            $table->char('schoolYearEnded',50)->nullable();
            $table->char('schoolAddress',200)->nullable();
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
        Schema::dropIfExists('emp_educations');
    }
};
