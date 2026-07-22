{{--
    Health Sanitary Card renewal banner (employee-facing, live — no persistent notice).
    Expects $emp = the viewer's empDetail (may be null). Renders nothing unless the card
    is already expired (danger) or expiring within 30 days (warning). Included on the
    employee's own 201 self-view and home landing page.
--}}
@php
    $scBannerEmp = $emp ?? null;
    $scBannerExp = ($scBannerEmp && $scBannerEmp->empSanitaryCardExpDate)
        ? \Carbon\Carbon::parse($scBannerEmp->empSanitaryCardExpDate)
        : null;
    $scBannerDays = $scBannerExp ? \Carbon\Carbon::today()->diffInDays($scBannerExp, false) : null;
@endphp
@if($scBannerExp !== null && $scBannerDays <= 30)
    @php $scExpired = $scBannerDays < 0; @endphp
    <div class="alert {{ $scExpired ? 'alert-danger' : 'alert-warning' }} alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm"
         role="alert" style="border-radius:12px;">
        <i class="fa-solid fa-notes-medical fa-lg"></i>
        <div class="flex-grow-1">
            @if($scExpired)
                <strong>Your Health Sanitary Card has expired.</strong>
                It expired on <strong>{{ $scBannerExp->format('M d, Y') }}</strong> ({{ abs($scBannerDays) }} day{{ abs($scBannerDays) === 1 ? '' : 's' }} ago).
                Please renew it and give the updated details to HR.
            @else
                <strong>Your Health Sanitary Card is expiring soon.</strong>
                It expires on <strong>{{ $scBannerExp->format('M d, Y') }}</strong>
                ({{ $scBannerDays === 0 ? 'today' : 'in ' . $scBannerDays . ' day' . ($scBannerDays === 1 ? '' : 's') }}).
                Please arrange to renew it before it lapses.
            @endif
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
