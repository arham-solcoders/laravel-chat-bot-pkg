<?php

declare(strict_types=1);

namespace DbChatbot;

use DbChatbot\Chat\Drivers\ChatDriver;
use DbChatbot\Chat\Drivers\RuleBasedDriver;
use DbChatbot\Console\Commands\PruneReports;
use DbChatbot\Models\Customer;
use DbChatbot\Models\Order;
use DbChatbot\Models\Product;
use DbChatbot\Policies\CustomerPolicy;
use DbChatbot\Policies\OrderPolicy;
use DbChatbot\Policies\ProductPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class DbChatbotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/chatbot.php', 'chatbot');

        $this->app->bind(ChatDriver::class, function ($app): ChatDriver {
            if (config('chatbot.driver') === 'rules') {
                return $app->make(RuleBasedDriver::class);
            }

            throw new RuntimeException('Chatbot driver ['.config('chatbot.driver').'] is not available.');
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'db-chatbot');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneReports::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/chatbot.php' => config_path('chatbot.php'),
        ], 'db-chatbot-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'db-chatbot-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/db-chatbot'),
        ], 'db-chatbot-views');

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);

        Gate::define('viewSales', fn (object $user): bool => method_exists($user, 'managesOrders') && $user->managesOrders());
        Gate::define('viewAudit', fn (object $user): bool => method_exists($user, 'isAdmin') && $user->isAdmin());

        if (config('filesystems.disks.reports') === null) {
            config([
                'filesystems.disks.reports' => [
                    'driver' => 'local',
                    'root' => storage_path('app/reports'),
                    'throw' => true,
                ],
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('reports:prune')->daily();
        });
    }
}
