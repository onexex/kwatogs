@extends('kubo.layout.kubo')
@section('kubo-content')
<div class="container" style="max-width: 635px;">
    <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0"><i class="far fa-bell me-2"></i>Notifications</h6>
            <button id="kuboMarkAllRead" class="btn btn-link btn-sm text-decoration-none" style="color: #008080;">Mark all read</button>
        </div>
    </div>
    <div id="kuboNotificationsList"></div>
    <div id="notificationsLoader" class="text-center py-4 d-none"><div class="spinner-border text-muted" style="width: 2rem; height: 2rem;"></div></div>
    <div id="notificationsEmpty" class="text-center py-5 d-none"><i class="far fa-bell-slash text-muted mb-3" style="font-size: 3rem;"></i><h6 class="text-muted">No notifications yet</h6><p class="text-muted small">When someone interacts with your posts, you'll see it here.</p></div>
</div>
@endsection