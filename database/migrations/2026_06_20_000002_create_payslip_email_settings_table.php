<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-row settings table (no FK needed — there's only ever one active
     * configuration) controlling how automated payslip emails behave.
     */
    public function up(): void
    {
        Schema::create('payslip_email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('password_source', 20)->default('birthdate'); // birthdate | employee_id | none
            $table->boolean('auto_send_on_approval')->default(false);
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_email_settings');
    }
};
