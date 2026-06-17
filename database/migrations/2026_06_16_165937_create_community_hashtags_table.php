<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_hashtags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('community_posts')->onDelete('cascade');
            $table->string('tag');
            $table->timestamps();

            $table->index('tag');
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_hashtags');
    }
};