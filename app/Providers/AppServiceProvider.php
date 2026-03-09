<?php

namespace App\Providers;

use App\Models\Leave;
use App\Observers\LeaveStatusObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Leave::observe(LeaveStatusObserver::class);
    }
}
