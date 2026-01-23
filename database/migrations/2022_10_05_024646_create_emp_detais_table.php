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
        Schema::create('emp_details', function (Blueprint $table) {
            $table->id();
            $table->char('empID',50)->nullable();
            $table->char('empISID',20)->nullable();
            $table->integer('empDepID')->nullable();
            $table->char('empCompID',50)->nullable();
            $table->char('empClassification',20)->nullable();
            $table->integer('empPos')->nullable(); 
            $table->char('empBasic',20)->nullable(); 
            $table->char('empStatus',20)->nullable(); 
            $table->char('empAllowance',20)->nullable(); 
            $table->char('empHrate',20)->nullable(); 
            $table->integer('empWday')->nullable();
            $table->char('empJobLevel',50)->nullable();
            $table->char('empAgencyID',100)->nullable();
            $table->char('empHMOID',100)->nullable();
            $table->char('empHMONo',100)->nullable();
            $table->char('empPicPath',50)->nullable(); 
            $table->date('empDateHired')->nullable();
            $table->date('empDateResigned')->nullable();
            $table->date('empDateRegular')->nullable();
            $table->char('empPrevPos',100)->nullable();
            $table->char('empPrevDep',100)->nullable();
            $table->char('empPrevWorkStartDate',100)->nullable();
            //ids
            $table->char('empPassport',100)->nullable();
            $table->date('empPassportExpDate')->nullable();
            $table->char('empPassportIssueAuth',100)->nullable();
            $table->char('empPagibig',100)->nullable();
            $table->char('empPhilhealth',100)->nullable();
            $table->char('empSSS',100)->nullable();
            $table->char('empTIN',100)->nullable();
            $table->char('empUMID',100)->nullable();
            $table->char('empPrevDesignation',250)->nullable();
           
            
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
        Schema::dropIfExists('emp_details');
    }
};
