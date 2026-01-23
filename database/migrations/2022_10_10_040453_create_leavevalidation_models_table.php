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
        Schema::create('leavevalidation_models', function (Blueprint $table) {
            $table->id();
            $table->char('compID')->nullable()->default('n/a');
            $table->integer('leave_type')->nullable();
            $table->integer('credits')->nullable();
            $table->integer('min_leave')->nullable();
            $table->integer('no_before_file')->nullable();
            $table->integer('no_after_file')->nullable();
            $table->integer('file_before')->nullable();
            $table->integer('file_after')->nullable();
            $table->integer('file_halfday')->nullable();
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
        Schema::dropIfExists('leavevalidation_models');
    }
};
