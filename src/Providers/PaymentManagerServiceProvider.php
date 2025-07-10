<?php

namespace PaymentManager\Providers;

use Illuminate\Support\ServiceProvider;
use PaymentManager\Contracts\PaymentManagerInterface;
use PaymentManager\Managers\PaymentManager;

class PaymentManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/laravel-payment-gateways.php', 'laravel-payment-gateways'
        );

        $this->app->singleton(PaymentManagerInterface::class, function ($app) {
            $config = $app['config']->get('laravel-payment-gateways', []);
            return new PaymentManager($config);
        });

        $this->app->alias(PaymentManagerInterface::class, 'laravel-payment-gateways');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/laravel-payment-gateways.php' => config_path('laravel-payment-gateways.php'),
        ], 'laravel-payment-gateways-config');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \PaymentManager\Console\Commands\DiagnosePaymentGateways::class,
            ]);
        }

        $this->registerRoutes();
    }

    /**
     * Register webhook routes
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        if (!$this->app->routesAreCached()) {
            $config = $this->app['config']->get('laravel-payment-gateways.webhooks', []);

            if ($config['enabled'] ?? true) {
                $router = $this->app['router'];
                $prefix = $config['route_prefix'] ?? 'payment/webhook';
                $middleware = $config['middleware'] ?? ['web'];

                $router->group([
                    'prefix' => $prefix,
                    'middleware' => $middleware,
                ], function ($router) {
                    $router->post('cinetpay', [\PaymentManager\Http\Controllers\WebhookController::class, 'cinetpay']);
                    $router->post('bizao', [\PaymentManager\Http\Controllers\WebhookController::class, 'bizao']);
                    $router->post('winipayer', [\PaymentManager\Http\Controllers\WebhookController::class, 'winipayer']);
                });
            }
        }
    }
}
