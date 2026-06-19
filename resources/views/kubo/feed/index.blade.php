@extends('kubo.layout.kubo')
@section('kubo-content')
<div class="kubo-feed-wrap">
    {{-- Left: Feed column --}}
    <div class="kubo-feed-main">
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

    {{-- Right: Desktop Sidebar --}}
    <div class="kubo-sidebar d-none d-lg-block">
        <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2">
                <span class="fw-bold small" style="color:#333;"><i class="fas fa-circle text-success me-1" style="font-size:0.55rem;"></i> Online Now</span>
                <small id="kuboOnlineCount" class="text-muted" style="font-size:0.7rem;">0 active</small>
            </div>
            <div id="kuboOnlineList" class="p-2" style="max-height:220px;overflow-y:auto;">
                <p class="text-muted text-center small py-2 mb-0">Loading...</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-2" style="border-radius:12px;overflow:hidden;">
            <div class="card-header bg-white border-bottom px-3 py-2">
                <span class="fw-bold small" style="color:#333;"><i class="far fa-comments me-1"></i> Recent Chats</span>
            </div>
            <div id="kuboConvList" class="p-2" style="max-height:220px;overflow-y:auto;">
                <p class="text-muted text-center small py-2 mb-0">Loading...</p>
            </div>
        </div>

    </div>
</div>

{{-- Floating chat bubble (desktop) --}}
<div id="kuboChatPanel" class="card border-0 shadow d-none" style="width:320px;border-radius:16px;overflow:hidden;">
    <div class="card-header d-flex align-items-center justify-content-between px-3 py-2" style="background:var(--teal, #008080);color:#fff;cursor:pointer;">
        <div class="d-flex align-items-center gap-2">
            <img id="kuboChatAvatar" src="" class="rounded-circle" width="28" height="28" style="object-fit:cover;" alt="">
            <span id="kuboChatName" class="fw-bold small"></span>
        </div>
        <button type="button" id="kuboChatClose" class="btn btn-link p-0 border-0 text-white" style="font-size:1.1rem;">&times;</button>
    </div>
    <div id="kuboChatMessages" class="p-2" style="height:280px;overflow-y:auto;background:#fafafa;"></div>
    <div class="p-2 border-top" style="background:#fff;">
        <div class="d-flex gap-2">
            <input type="text" id="kuboChatInput" class="form-control form-control-sm rounded-pill" placeholder="Type a message..." style="font-size:0.8rem;">
            <button id="kuboChatSend" class="btn btn-sm rounded-pill px-3 text-white" style="background:var(--teal, #008080);font-size:0.8rem;">Send</button>
        </div>
    </div>
</div>

<style>
    .kubo-feed-wrap { display:flex; gap:20px; max-width:1010px; margin:0 auto; padding:0 15px; }
    .kubo-feed-main { flex:1; max-width:635px; }
    .kubo-sidebar { width:280px; flex-shrink:0; }
    @media (max-width: 991px) { .kubo-feed-main { max-width:100%; } }
    .kubo-online-user { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; cursor:pointer; transition:background 0.15s; }
    .kubo-online-user:hover { background:#f0f9f9; }
    .kubo-online-user .dot { width:8px; height:8px; border-radius:50%; background:#10b981; box-shadow:0 0 0 2px rgba(16,185,129,.2); flex-shrink:0; }
    .chat-bubble { max-width:80%; padding:8px 12px; border-radius:14px; font-size:0.82rem; margin-bottom:6px; line-height:1.4; word-break:break-word; }
    .chat-bubble.mine { background:var(--teal, #008080); color:#fff; margin-left:auto; border-bottom-right-radius:4px; }
    .chat-bubble.theirs { background:#e8e8e8; color:#333; margin-right:auto; border-bottom-left-radius:4px; }
    :root { --teal:#008080; }

    /* Floating chat bubble — desktop */
    #kuboChatPanel {
        position: fixed; bottom: 20px; right: 20px; z-index: 1055;
        border-radius: 16px !important; overflow: hidden;
        box-shadow: 0 8px 32px rgba(0,0,0,.18) !important;
        animation: kuboChatPopIn .2s ease;
    }
    @keyframes kuboChatPopIn {
        from { opacity: 0; transform: translateY(10px) scale(.95); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
</style>

@include('kubo.components.create-post-modal')
@include('kubo.components.reaction-picker')
@endsection