<?php

namespace App\Providers;

use App\Services\Signature\ReceiptSourceResolverRegistry;
use App\Models\Payment;
use App\Observers\PaymentObserver;
use App\Services\Signature\Resolvers\MissionReceiptResolver;
use App\Services\Signature\Resolvers\PurchaseReceiptResolver;
use App\Services\Signature\Resolvers\RegularizationReceiptResolver;
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
         $this->app->bind(
        \App\Contracts\WorkflowAvailabilityClient::class,
        \App\Services\WorkflowAvailabilityHttpClient::class
    );


    $this->app->singleton(
        ReceiptSourceResolverRegistry::class,
        function ($app) {
            return new ReceiptSourceResolverRegistry([
                $app->make(RegularizationReceiptResolver::class),
                $app->make(PurchaseReceiptResolver::class),
                // $app->make(MissionReceiptResolver::class),
            ]);
        }
    );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Payment::observe(PaymentObserver::class);
    }
}
