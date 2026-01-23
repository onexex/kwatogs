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
        Schema::create('lilo_validations', function (Blueprint $table) {
            $table->id();
            $table->char('empCompID', 15);
            $table->char('lilo_gracePrd');
            $table->char('managersOverride', 11);
            $table->char('managersTime', 10);
            $table->timestamps();
        });
    }

    /**s
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lilo_validations');
    }
};
