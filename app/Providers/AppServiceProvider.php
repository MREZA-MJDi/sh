<?php

namespace App\Providers;

use App\Services\Quiz24\Contracts\Quiz24Gateway;
use App\Services\Quiz24\UnavailableQuiz24Gateway;
use App\Services\SpotPlayer\Contracts\SpotPlayerGateway;
use App\Services\SpotPlayer\UnavailableSpotPlayerGateway;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SpotPlayerGateway::class, UnavailableSpotPlayerGateway::class);
        $this->app->bind(Quiz24Gateway::class, UnavailableQuiz24Gateway::class);
    }

    public function boot(): void
    {
        Gate::before(function ($user, string $ability): ?bool {
            return $user->hasPermissionTo($ability) ? true : null;
        });
    }
}
