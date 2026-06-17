@extends('kubo.layout.kubo')
@section('kubo-content')
<div class="container" style="max-width: 935px;">
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center">
                <img src="{{ $profileUser->community_avatar }}" class="rounded-circle me-4" width="80" height="80" style="object-fit: cover; border: 3px solid #e0e0e0;">
                <div>
                    <h5 class="fw-bold mb-1">{{ $profileUser->community_full_name }}</h5>
                    <small class="text-muted">
                        {{ $profileUser->empDetail?->department?->depName ?? 'N/A' }}
                        @if($profileUser->empDetail?->position?->posDesc) · {{ $profileUser->empDetail->position->posDesc }} @endif
                    </small>
                    <div class="d-flex gap-3 mt-2" id="profileStats" data-user-id="{{ $profileUser->id }}">
                        <div><strong id="statPosts">0</strong> <small class="text-muted">Posts</small></div>
                        <div><strong id="statReactions">0</strong> <small class="text-muted">Reactions</small></div>
                        <div><strong id="statComments">0</strong> <small class="text-muted">Comments</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <ul class="nav nav-pills justify-content-center mb-3 border-top pt-3" id="profileTabs">
        <li class="nav-item"><button class="nav-link active" data-tab="posts"><i class="fas fa-th me-1"></i> Posts</button></li>
        <li class="nav-item"><button class="nav-link" data-tab="photos"><i class="fas fa-images me-1"></i> Photos</button></li>
        <li class="nav-item"><button class="nav-link" data-tab="reposts"><i class="fas fa-retweet me-1"></i> Reposts</button></li>
    </ul>
    <div id="profileTabContent" class="row g-3"></div>
    <div id="profileLoader" class="text-center py-4 d-none"><div class="spinner-border text-muted"></div></div>
</div>
@endsection