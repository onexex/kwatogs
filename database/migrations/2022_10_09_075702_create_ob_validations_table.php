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
        Schema::create('ob_validations', function (Blueprint $table) {
            $table->id();
            $table->char('ob_fBefore', 20)->nullable()->default('0');
            $table->integer('ob_dBefore')->nullable();
            $table->char('ob_fAfter', 20)->nullable()->default('0');
            $table->integer('ob_dAfter')->nullable();
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
        Schema::dropIfExists('ob_validations');
    }
};
