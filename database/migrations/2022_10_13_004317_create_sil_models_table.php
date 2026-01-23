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
        Schema::create('silLoan', function (Blueprint $table) {
            $table->id();
            $table->text('silEmpID');
            $table->text('silAmount');
            $table->text('silType');
            $table->text('silStatus');
            $table->date('silDate');
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
        Schema::dropIfExists('silLoan');
    }
};
