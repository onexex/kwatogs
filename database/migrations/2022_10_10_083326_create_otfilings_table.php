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
        Schema::create('otfilings', function (Blueprint $table) {
            $table->id();
            $table->char('comp_id',20)->nullable();
            $table->integer('filebefore')->nullable();
            $table->integer('fileafter')->nullable();
            $table->integer('no_days_before')->nullable();
            $table->integer('no_days_after')->nullable();
            $table->integer('holiday')->nullable();
            $table->integer('tardy')->nullable();
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
        Schema::dropIfExists('otfilings');
    }
};
