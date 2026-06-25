<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_settings', function (Blueprint $table) {
            $table->id();
            // Master switch.
            $table->boolean('is_active')->default(false);
            // 'global' = everyone is locked out; 'department' = only selected departments.
            $table->string('scope', 20)->default('global');
            // Department ids affected when scope = 'department'. Null/[] for global.
            $table->json('department_ids')->nullable();
            // Message shown on the maintenance/lockout screen.
            $table->text('message')->nullable();
            // Optional scheduling window. Null = takes effect immediately / no end.
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('updated_by', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_settings');
    }
};
