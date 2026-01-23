<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('emp_families', function (Blueprint $table) {
            $table->id();
            $table->char('empID',20)->nullable();
            $table->char('famName',30)->nullable();
            $table->integer('famRelationID')->nullable();            
            $table->char('famRelationDesc',30)->nullable();            
            $table->char('famContact')->nullable();  
            $table->integer('famICE')->nullable();            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emp_families');
    }
};
