<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('community_posts')->onDelete('cascade');
            $table->char('user_id', 255);
            $table->enum('reaction_type', ['love', 'like', 'fire', 'laugh', 'clap', 'celebrate']);
            $table->timestamps();

            $table->unique(['post_id', 'user_id']);
            $table->index(['post_id', 'reaction_type']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_post_reactions');
    }
};