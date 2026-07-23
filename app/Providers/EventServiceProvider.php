<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        // Successful login/logout are intentionally NOT audited here: they are
        // high-volume (every employee, multiple times a day) and would flood the
        // audit_logs table, while successful logins are already tracked separately
        // in IpAccessLog (loginCtrl@loginSystem -> Allowed IPs dashboard).
        //
        // Only FAILED logins are audited, and that happens directly in loginCtrl
        // (login-failed) because this app validates credentials manually (no
        // Auth::attempt) — failed logins are low-volume and security-relevant.
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
