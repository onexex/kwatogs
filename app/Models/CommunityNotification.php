<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityNotification extends Model
{
    use HasFactory;

    protected $table = 'community_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'reference_id',
        'actor_id',
        'message',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'empID');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id', 'empID');
    }

    public static function typeLabels(): array
    {
        return [
            'reaction' => 'reacted to your post',
            'comment' => 'commented on your post',
            'reply' => 'replied to your comment',
            'repost' => 'reposted your post',
            'admin_announcement' => 'posted an announcement',
        ];
    }
}