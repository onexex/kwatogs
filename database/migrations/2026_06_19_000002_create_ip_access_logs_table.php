<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('ip_address', 45);
            $table->enum('status', ['allowed', 'blocked']);
            $table->enum('action_type', ['login', 'access'])->default('login');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['action_type', 'created_at']);
            $table->index('user_id');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_access_logs');
    }
};
