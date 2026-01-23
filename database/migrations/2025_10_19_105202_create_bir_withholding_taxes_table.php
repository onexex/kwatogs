<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bir_withholding_taxes', function (Blueprint $table) {
            $table->id();

            // Annual income brackets
            $table->decimal('compensation_from', 15, 2);
            $table->decimal('compensation_to', 15, 2)->nullable();

            // Fixed amount and excess percentage
            $table->decimal('fixed_tax', 15, 2)->default(0);
            $table->decimal('excess_over', 15, 2)->default(0);
            $table->decimal('percent_over', 5, 2)->default(0);

            // For version/year tracking
            $table->year('effective_year')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bir_withholding_taxes');
    }
};
