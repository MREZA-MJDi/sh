<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UploadRateLimitTest extends TestCase
{
    public function test_each_upload_stage_has_a_named_rate_limiter_for_admin_and_teacher_routes(): void
    {
        $stages = ['start', 'resume', 'status', 'chunk', 'complete', 'cancel'];

        foreach ($stages as $stage) {
            $limiterName = "upload-{$stage}";

            $this->assertNotNull(
                RateLimiter::limiter($limiterName),
                "The {$limiterName} rate limiter must be registered."
            );

            foreach (['admin', 'teacher'] as $portal) {
                $route = Route::getRoutes()->getByName("{$portal}.uploads.{$stage}");

                $this->assertNotNull($route, "Missing {$portal} upload {$stage} route.");
                $this->assertContains(
                    "throttle:{$limiterName}",
                    $route->gatherMiddleware(),
                    "{$portal} upload {$stage} must use its dedicated limiter."
                );
            }
        }
    }
}
