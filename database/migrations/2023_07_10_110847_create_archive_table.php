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
        Schema::create('archive', function (Blueprint $table) {
            $table->id();
            $table->char('fname', 250)->nullable();
            $table->char('lname', 250)->nullable();
            $table->char('pos', 250)->nullable();
            $table->char('empDatesFrom', 250)->nullable();
            $table->char('empDatesTo', 250)->nullable();
            $table->char('empStatus', 250)->nullable();
            $table->char('clearance', 250)->nullable();
            $table->char('reasonForLeaving', 250)->nullable();
            $table->char('derogatoryRecords', 250)->nullable();
            $table->char('salary', 250)->nullable();
            $table->char('pendingResign', 250)->nullable();
            $table->char('addRemarks', 250)->nullable();
            $table->char('verifiedBy', 250)->nullable();
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
        Schema::dropIfExists('archive');
    }
};
