<?php

use App\Http\Controllers\Admin\ChunkUploadController;
use App\Http\Controllers\Admin\LearningAdminController;
use App\Http\Controllers\Learning\CourseController;
use App\Http\Controllers\Learning\EnrollmentController;
use App\Http\Controllers\Learning\JoinClassController;
use App\Http\Controllers\Learning\LessonController;
use Illuminate\Support\Facades\Route;

// در انتهای routes/web.php:  require __DIR__.'/learning.php';

// ---------- عمومی / دانش‌آموز ----------
Route::get('courses/{slug}', [CourseController::class, 'show'])->name('courses.show');

Route::middleware('auth')->group(function () {
    Route::post('courses/{slug}/enroll', EnrollmentController::class)->name('courses.enroll')->middleware('throttle:10,1');
    Route::get('courses/{slug}/access-status', [CourseController::class, 'accessStatus'])->name('courses.access-status')->middleware('throttle:60,1');
    Route::get('courses/{slug}/join', JoinClassController::class)->name('courses.join')->middleware('throttle:20,1');
    Route::get('courses/{slug}/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
    Route::post('courses/{slug}/lessons/{lesson}/complete', [LessonController::class, 'toggleComplete'])->name('lessons.complete');
});

// ---------- ادمین ----------
// نام مجوز را با مجوز واقعی پروژه عوض کن.
Route::middleware(['auth', 'can:courses.manage'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('uploads/chunk', [ChunkUploadController::class, 'chunk'])->name('uploads.chunk');
    Route::post('uploads/complete', [ChunkUploadController::class, 'complete'])->name('uploads.complete');

    Route::get('courses/{course}/teaser-status', [LearningAdminController::class, 'teaserStatus'])->name('courses.teaser.status');
    Route::delete('courses/{course}/teaser', [LearningAdminController::class, 'destroyTeaser'])->name('courses.teaser.destroy');

    Route::post('licenses/{license}/retry', [LearningAdminController::class, 'retryLicense'])->name('licenses.retry');
    Route::post('lessons/{lesson}/video/retry', [LearningAdminController::class, 'retryLessonVideo'])->name('lessons.video.retry');
});
