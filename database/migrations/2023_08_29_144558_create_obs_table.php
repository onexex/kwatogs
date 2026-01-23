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
        Schema::create('obs', function (Blueprint $table) {
            $table->id('obID');
            $table->char('empID', 50)->nullable();
            $table->char('empISID', 50)->nullable();
            $table->date('obFD')->nullable();
            $table->date('obDateFrom')->nullable();
            $table->date('obDateTo')->nullable();

            $table->char('obIFrom', 50)->nullable();
            $table->char('obITo', 50)->nullable();

            $table->decimal('obCAAmt', 8, 2)->nullable();
            $table->char('obCAPurpose', 100)->nullable();

            $table->char('obPurpose', 100)->nullable();
            $table->char('obTFrom', 10)->nullable();
            $table->char('obTTo', 10)->nullable();
            $table->char('obDuration', 50)->nullable();

            $table->char('obStatus', 10)->nullable();
            $table->char('obISReason', 100)->nullable();
            $table->char('obHRReason', 100)->nullable();
            $table->char('obType', 10)->nullable();
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
        Schema::dropIfExists('obs');
    }
};
