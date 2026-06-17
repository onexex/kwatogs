@extends('kubo.layout.kubo')
@section('kubo-content')
<div class="container" style="max-width: 635px;">
    <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="d-flex align-items-center">
                <img src="{{ auth()->user()->community_avatar }}" class="rounded-circle me-3" width="42" height="42" style="object-fit: cover;">
                <button class="btn btn-light text-muted text-start w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#createPostModal" style="height: 42px; background: #f2f2f2; border: none;">
                    What's on your mind, {{ explode(' ', auth()->user()->community_full_name)[0] }}?
                </button>
            </div>
        </div>
    </div>
    <div id="kuboFeedContainer"></div>
    <div id="kuboFeedLoader" class="text-center py-4 d-none"><div class="spinner-border text-muted" style="width: 2rem; height: 2rem;"></div></div>
    <div id="kuboFeedEnd" class="text-center py-4 d-none"><i class="far fa-smile text-muted mb-2" style="font-size: 2rem;"></i><p class="text-muted small mb-0">You're all caught up!</p></div>
</div>
@include('kubo.components.create-post-modal')
@include('kubo.components.reaction-picker')
@endsection