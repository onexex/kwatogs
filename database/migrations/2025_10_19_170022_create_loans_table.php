<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');


            // loan source: salary, sss, pagibig, philhealth
            $table->enum('loan_type', ['salary', 'sss', 'pagibig', 'philhealth']);

            $table->decimal('loan_amount', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->decimal('monthly_amortization', 12, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'paid', 'cancelled'])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
