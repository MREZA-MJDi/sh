<?php

namespace App\Providers;

use App\Services\Learning\LearningProvider;
use App\Services\Learning\PendingContractProvider;
use Illuminate\Support\ServiceProvider;

class LearningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // بعد از تحویل API رسمی: PendingContractProvider را با Adapter واقعی عوض کن.
        $this->app->bind(LearningProvider::class, PendingContractProvider::class);
    }
}
