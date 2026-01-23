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
        Schema::create('home_attendance', function (Blueprint $table) {
            $table->id();

            $table->char('empID', 20)->nullable();
            $table->char('wsched', 50)->nullable();
            $table->char('wsched2', 50)->nullable();
            $table->date('wsFrom')->nullable();
            $table->date('wsTo')->nullable();
            $table->timestamp('timeIn')->nullable();
            $table->timestamp('timeOut')->nullable();
            $table->char('minsLack', 25)->nullable();
            $table->char('minsLack2', 25)->nullable();
            $table->timestamp('dateTimeInput')->nullable();
            $table->char('durationTime', 50)->nullable();
            $table->char('nightD', 20)->nullable();

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
        Schema::dropIfExists('home_attendances');
    }
};
