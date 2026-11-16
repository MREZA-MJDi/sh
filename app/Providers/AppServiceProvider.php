<?php

namespace App\\Providers;

use App\\Auth\\LegacyAwareUserProvider;
use App\\Services\\Quiz24\\Contracts\\Quiz24Gateway;
use App\\Services\\Quiz24\\UnavailableQuiz24Gateway;
use App\\Services\\Skyroom\\Contracts\\SkyroomGateway;
use App\\Services\\Skyroom\\UnavailableSkyroomGateway;
use App\\Services\\SpotPlayer\\Contracts\\SpotPlayerGateway;
use App\\Services\\SpotPlayer\\UnavailableSpotPlayerGateway;
use Illuminate\\Support\\Facades\\Auth;
use Illuminate\\Support\\Facades\\Blade;
use Illuminate\\Support\\Facades\\Gate;
use Illuminate\\Support\\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SpotPlayerGateway::class, UnavailableSpotPlayerGateway::class);
        $this->app->bind(Quiz24Gateway::class, UnavailableQuiz24Gateway::class);
        $this->app->bind(SkyroomGateway::class, UnavailableSkyroomGateway::class);
    }

    public function boot(): void
    {
        Auth::provider('legacy-aware-eloquent', function ($app, array $config): LegacyAwareUserProvider {
            return new LegacyAwareUserProvider($app['hash'], $config['model']);
        });

        Blade::anonymousComponentPath(resource_path('views/admin/components'), 'admin');

        Gate::before(function ($user, string $ability): ?bool {
            return $user->hasPermissionTo($ability) ? true : null;
        });
    }
}
