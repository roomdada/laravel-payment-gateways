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
            __DIR__ . '/../../config/payment-manager.php', 'payment-manager'
        );

        $this->app->singleton(PaymentManagerInterface::class, function ($app) {
            $config = $app['config']->get('payment-manager', []);
            return new PaymentManager($config);
        });

        $this->app->alias(PaymentManagerInterface::class, 'payment-manager');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/payment-manager.php' => config_path('payment-manager.php'),
        ], 'payment-manager-config');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

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
            $config = $this->app['config']->get('payment-manager.webhooks', []);

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
