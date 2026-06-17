<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_notifications', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 255);
            $table->enum('type', ['reaction', 'comment', 'reply', 'repost', 'admin_announcement']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->char('actor_id', 255)->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_notifications');
    }
};