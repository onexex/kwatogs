@extends('layout.app')
@section('content')
@php
    $feedActive = request()->is('kubo') && !request()->is('kubo/*');
    $exploreActive = request()->is('kubo/explore*');
    $notificationsActive = request()->is('kubo/notifications*');
    $profileActive = request()->is('kubo/profile*');
@endphp

<div class="kubo-navbar bg-white border-bottom mb-0 sticky-top" style="z-index: 1020;">
    <div class="container" style="max-width: 935px;">
        <div class="d-flex align-items-center justify-content-between py-2">
            <a href="{{ route('kubo.feed') }}" class="text-decoration-none d-flex align-items-center">
                <span style="font-size: 1.5rem; font-weight: 700; color: #008080; letter-spacing: -1px;">
                    <i class="fas fa-users me-2"></i>KwHub
                </span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0)" id="kuboMessagesToggle" class="kubo-nav-link d-lg-none position-relative" title="Messages">
                    <i class="far fa-comment-dots fa-lg"></i>
                    <span id="kuboMsgBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size:0.6rem;">0</span>
                </a>
                <a href="{{ route('kubo.feed') }}" class="kubo-nav-link {{ $feedActive ? 'active' : '' }}"><i class="fas fa-home fa-lg"></i></a>
                <a href="{{ route('kubo.explore') }}" class="kubo-nav-link {{ $exploreActive ? 'active' : '' }}"><i class="far fa-compass fa-lg"></i></a>
                <a href="{{ route('kubo.notifications') }}" class="kubo-nav-link position-relative {{ $notificationsActive ? 'active' : '' }}">
                    <i class="far fa-bell fa-lg"></i>
                    <span id="kuboNotificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size: 0.6rem;">0</span>
                </a>
                <a href="{{ route('kubo.profile') }}" class="kubo-nav-link {{ $profileActive ? 'active' : '' }}">
                    <img src="{{ auth()->user()->community_avatar }}" class="rounded-circle border" width="26" height="26" style="object-fit: cover;">
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .kubo-nav-link { color: #262626; padding: 6px 8px; border-radius: 8px; transition: all 0.2s; }
    .kubo-nav-link:hover { background: #f2f2f2; color: #000; }
    .kubo-nav-link.active { color: #008080; }
    body { background-color: #FAFAFA !important; }
    #content-wrapper { background-color: #FAFAFA !important; }
</style>

<div id="kuboScroll" class="py-3">@yield('kubo-content')</div>

{{-- Mobile offcanvas sidebar for messages/online --}}
<div class="offcanvas offcanvas-end kubo-offcanvas d-lg-none" tabindex="-1" id="kuboMsgOffcanvas" style="width:300px;">
    <div class="offcanvas-header border-bottom" style="background:#fafafa;">
        <h6 class="offcanvas-title fw-bold small"><i class="fas fa-comment-dots me-1" style="color:var(--teal, #008080);"></i> Messages & Online</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-2" id="kuboOffcanvasBody">
        <div class="card border-0 shadow-sm mb-2" style="border-radius:10px;overflow:hidden;">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2">
                <span class="fw-bold small" style="color:#333;"><i class="fas fa-circle text-success me-1" style="font-size:0.55rem;"></i> Online Now</span>
                <small id="kuboOnlineCountMob" class="text-muted" style="font-size:0.7rem;"></small>
            </div>
            <div id="kuboOnlineListMob" class="p-2" style="max-height:200px;overflow-y:auto;"></div>
        </div>
        <div class="card border-0 shadow-sm mb-2" style="border-radius:10px;overflow:hidden;">
            <div class="card-header bg-white border-bottom px-3 py-2">
                <span class="fw-bold small" style="color:#333;"><i class="far fa-comments me-1"></i> Recent Chats</span>
            </div>
            <div id="kuboConvListMob" class="p-2" style="max-height:200px;overflow-y:auto;"></div>
        </div>
        {{-- Chat panel inside offcanvas --}}
        <div id="kuboChatPanelMob" class="card border-0 shadow-sm d-none" style="border-radius:10px;overflow:hidden;">
            <div class="card-header d-flex align-items-center justify-content-between px-3 py-2" style="background:var(--teal, #008080);color:#fff;">
                <div class="d-flex align-items-center gap-2">
                    <img id="kuboChatAvatarMob" src="" class="rounded-circle" width="26" height="26" style="object-fit:cover;" alt="">
                    <span id="kuboChatNameMob" class="fw-bold small"></span>
                </div>
                <button type="button" id="kuboChatCloseMob" class="btn btn-link p-0 border-0 text-white" style="font-size:1rem;">&times;</button>
            </div>
            <div id="kuboChatMessagesMob" class="p-2" style="height:280px;overflow-y:auto;background:#fafafa;"></div>
            <div class="p-2 border-top" style="background:#fff;">
                <div class="d-flex gap-2">
                    <input type="text" id="kuboChatInputMob" class="form-control form-control-sm rounded-pill" placeholder="Type a message..." style="font-size:0.8rem;">
                    <button id="kuboChatSendMob" class="btn btn-sm rounded-pill px-3 text-white" style="background:var(--teal, #008080);font-size:0.8rem;">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Lock the outer page so only the feed scrolls */
    #kuboScroll { overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch; }
    #kuboScroll::-webkit-scrollbar { width: 8px; }
    #kuboScroll::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }

    /* Because KuBo locks the page (body overflow hidden) so only the feed
       scrolls, the left menu would otherwise be frozen and its lower items
       unreachable. Give the sidebar its own internal scroll while on KuBo. */
    body.kubo-page #accordionSidebar {
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
    }
    body.kubo-page #accordionSidebar::-webkit-scrollbar { width: 8px; }
    body.kubo-page #accordionSidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.25); border-radius: 4px; }
    body.kubo-page #accordionSidebar::-webkit-scrollbar-track { background: transparent; }
</style>

<script src="{{ asset('js/kubo/kubo.js') }}?v={{ time() }}"></script>
@stack('kubo-scripts')
<script src="{{ asset('js/kubo/kubo-chat.js') }}?v={{ time() }}"></script>
<script>
    (function () {
        var scroll = document.getElementById('kuboScroll');
        if (!scroll) return;

        // Marks the page so the sidebar gets its own scroll (see CSS above) —
        // the locked body would otherwise freeze the left menu.
        document.body.classList.add('kubo-page');

        function sizeFeed() {
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            var top = scroll.getBoundingClientRect().top;
            scroll.style.height = (window.innerHeight - top) + 'px';
        }

        sizeFeed();
        window.addEventListener('resize', sizeFeed);
        window.addEventListener('load', sizeFeed);
    })();
</script>
@endsection