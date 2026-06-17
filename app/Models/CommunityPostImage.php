<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityPostImage extends Model
{
    use HasFactory;

    protected $table = 'community_post_images';

    protected $fillable = [
        'post_id',
        'image_path',
        'sort_order',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }
}