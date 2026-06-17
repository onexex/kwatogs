<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'community_posts';

    protected $fillable = [
        'user_id',
        'content',
        'visibility',
        'is_pinned',
        'is_announcement',
        'pinned_by',
        'pinned_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_announcement' => 'boolean',
        'pinned_at' => 'datetime',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'empID');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CommunityPostImage::class, 'post_id')->orderBy('sort_order');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommunityPostReaction::class, 'post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'post_id')->whereNull('parent_id')->orderBy('created_at', 'asc');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'post_id');
    }

    public function reposts(): HasMany
    {
        return $this->hasMany(CommunityRepost::class, 'post_id');
    }

    public function hashtags(): HasMany
    {
        return $this->hasMany(CommunityHashtag::class, 'post_id');
    }

    // Accessors

    public function getReactionsCountAttribute(): int
    {
        return $this->reactions()->count();
    }

    public function getCommentsCountAttribute(): int
    {
        return $this->allComments()->count();
    }

    public function getRepostsCountAttribute(): int
    {
        return $this->reposts()->count();
    }

    public function getReactionSummaryAttribute(): array
    {
        return $this->reactions()
            ->selectRaw('reaction_type, COUNT(*) as count')
            ->groupBy('reaction_type')
            ->pluck('count', 'reaction_type')
            ->toArray();
    }

    // Scopes

    public function scopeFeed($query)
    {
        return $query->with([
            'user.empDetail.department',
            'user.empDetail.position',
            'images',
            'hashtags',
        ])
            ->withCount(['reactions', 'allComments as comments_count', 'reposts'])
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');
    }
}