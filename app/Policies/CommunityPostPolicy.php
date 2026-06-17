<?php

namespace App\Policies;

use App\Models\CommunityPost;
use App\Models\User;

class CommunityPostPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, CommunityPost $post): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CommunityPost $post): bool
    {
        return $user->empID === $post->user_id;
    }

    public function delete(User $user, CommunityPost $post): bool
    {
        return $user->empID === $post->user_id || $user->can('kwhubadmin');
    }

    public function pin(User $user): bool
    {
        return $user->can('kwhubadmin');
    }

    public function restore(User $user, CommunityPost $post): bool
    {
        return $user->can('kwhubadmin');
    }

    public function forceDelete(User $user, CommunityPost $post): bool
    {
        return $user->can('kwhubadmin');
    }
}