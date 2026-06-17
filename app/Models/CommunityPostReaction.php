<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityPostReaction extends Model
{
    use HasFactory;

    protected $table = 'community_post_reactions';

    protected $fillable = [
        'post_id',
        'user_id',
        'reaction_type',
    ];

    protected $casts = [
        'reaction_type' => 'string',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'empID');
    }

    public static function reactionEmojis(): array
    {
        return [
            'love' => '❤️',
            'like' => '👍',
            'fire' => '🔥',
            'laugh' => '😂',
            'clap' => '👏',
            'celebrate' => '🎉',
        ];
    }
}