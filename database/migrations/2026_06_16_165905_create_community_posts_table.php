<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 255);
            $table->text('content')->nullable();
            $table->enum('visibility', ['public', 'connections_only'])->default('public');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_announcement')->default(false);
            $table->char('pinned_by', 255)->nullable();
            $table->timestamp('pinned_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['is_pinned', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_posts');
    }
};