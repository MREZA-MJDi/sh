<?php

use App\Http\Controllers\Portal\LearningResourceController as PortalLearningResourceController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\EnrollFreeCourseController;
use App\Http\Controllers\Student\JoinClassSessionController;
use App\Http\Controllers\Student\SpotPlayerLicenseController as StudentSpotPlayerLicenseController;
use App\Http\Controllers\Student\VideoController as StudentVideoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Learning routes
|--------------------------------------------------------------------------
|
| Public course discovery and course detail remain owned by PublicSiteController
| in web.php. This file contains only authenticated learning actions and does
| not register a second /courses/{slug} route or a parallel enrollment model.
|
*/

Route::middleware(['auth', 'role:student', 'permission:dashboard.view'])
    ->prefix('student')
    ->name('student.')
    ->group(function (): void {
        Route::post('/class-sessions/{classSession}/join', JoinClassSessionController::class)
            ->middleware('throttle:20,1')
            ->name('class-sessions.join');

        Route::middleware('permission:videos.view.enrolled')->group(function (): void {
            Route::get('/videos', [StudentVideoController::class, 'index'])->name('videos.index');
            Route::get('/videos/{video}', [StudentVideoController::class, 'show'])->name('videos.show');
            Route::get('/videos/{video}/stream', [StudentVideoController::class, 'stream'])->name('videos.stream');
        });

        Route::middleware('permission:lessons.view')->group(function (): void {
            Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses.index');
            Route::post('/courses/{course}/spotplayer-license', [StudentSpotPlayerLicenseController::class, 'store'])
                ->middleware('throttle:3,1')
                ->name('courses.spotplayer-license.store');
            Route::post('/courses/{course}/enroll-free', EnrollFreeCourseController::class)
                ->middleware('throttle:10,1')
                ->name('courses.enroll-free');
            Route::get('/courses/{course}', [StudentCourseController::class, 'show'])->name('courses.show');
            Route::patch('/courses/{course}/lessons/{lesson}/progress', [StudentCourseController::class, 'updateProgress'])
                ->middleware('permission:lessons.progress')
                ->name('courses.lessons.progress');
        });

        Route::get('/resources/{resource}/download', [PortalLearningResourceController::class, 'download'])
            ->middleware('permission:resources.view.enrolled')
            ->name('resources.download');
    });

Route::middleware(['auth', 'role:parent'])
    ->prefix('parent')
    ->name('guardian.')
    ->group(function (): void {
        Route::get('/resources/{resource}/download', [PortalLearningResourceController::class, 'download'])
            ->middleware('permission:resources.view.enrolled')
            ->name('resources.download');
    });
