<?php

namespace App\Banking\Payments\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

use App\Banking\Payments\Application\Services\PaymentGatewayResolver;

// Adapters
use App\Banking\Payments\Infrastructure\Adapters\CardProcessorGatewayAdapter;
use App\Banking\Payments\Infrastructure\Adapters\InternationalWireGatewayAdapter;
use App\Banking\Payments\Infrastructure\Adapters\LegacyCoreGatewayAdapter;

// Clients
use App\Banking\Payments\Infrastructure\Clients\CardProcessorClient;
use App\Banking\Payments\Infrastructure\Clients\InternationalWireClient;
use App\Banking\Payments\Infrastructure\Clients\LegacyCoreClient;

final class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /**
         * Clients (external systems)
         * Keep them simple for the assignment.
         */
        $this->app->singleton(CardProcessorClient::class, fn () => new CardProcessorClient());
        $this->app->singleton(InternationalWireClient::class, fn () => new InternationalWireClient());
        $this->app->singleton(LegacyCoreClient::class, fn () => new LegacyCoreClient());

        /**
         * Adapters (Adapter Pattern)
         */
        $this->app->singleton(CardProcessorGatewayAdapter::class, function ($app) {
            return new CardProcessorGatewayAdapter($app->make(CardProcessorClient::class));
        });

        $this->app->singleton(InternationalWireGatewayAdapter::class, function ($app) {
            return new InternationalWireGatewayAdapter($app->make(InternationalWireClient::class));
        });

        $this->app->singleton(LegacyCoreGatewayAdapter::class, function ($app) {
            return new LegacyCoreGatewayAdapter($app->make(LegacyCoreClient::class));
        });

        /**
         * ✅ Fix: bind resolver with the array map
         * This solves: Unresolvable dependency (array $gateways)
         */
        $this->app->singleton(PaymentGatewayResolver::class, function ($app) {
            $gateways = [
                'card'   => $app->make(CardProcessorGatewayAdapter::class),
                'wire'   => $app->make(InternationalWireGatewayAdapter::class),
                'legacy' => $app->make(LegacyCoreGatewayAdapter::class),
            ];

            return new PaymentGatewayResolver($gateways);
        });
    }
}
