<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('loan_id');
            $table->foreignId('payroll_id');

            // Payment details
            $table->decimal('amount_paid', 12, 2);
            $table->date('payment_date');
            $table->string('remarks')->nullable(); // e.g. "Auto-deducted via payroll"

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
