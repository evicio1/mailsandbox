<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use App\Models\Tenant;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Events\WebhookHandled;
use App\Listeners\StripeEventListener;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(Tenant::class);

        Event::listen(
            WebhookHandled::class,
            StripeEventListener::class
        );
    }
}
