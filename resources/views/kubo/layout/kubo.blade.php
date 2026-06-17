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
                    <i class="fas fa-users me-2"></i>KuBo
                </span>
            </a>
            <div class="d-flex align-items-center gap-3">
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

<style>
    /* Lock the outer page so only the feed scrolls */
    #kuboScroll { overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch; }
    #kuboScroll::-webkit-scrollbar { width: 8px; }
    #kuboScroll::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
</style>

<script src="{{ asset('js/kubo/kubo.js') }}?v={{ time() }}"></script>
<script>
    (function () {
        var scroll = document.getElementById('kuboScroll');
        if (!scroll) return;

        function sizeFeed() {
            // Lock body/window scroll so the feed is the only scrollable area
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