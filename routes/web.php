<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\ConsultationController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\HomeworkController;
use App\Http\Controllers\Admin\LearningResourceController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\Quiz24Controller;
use App\Http\Controllers\Admin\SpotPlayerController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AdminLoginController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminLoginController::class, 'store'])->middleware('throttle:6,1')->name('admin.login.store');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');

    Route::get('/students', [UserController::class, 'index'])->defaults('role', 'student')->middleware('permission:users.manage')->name('students.index');
    Route::post('/students', [UserController::class, 'store'])->defaults('role', 'student')->middleware('permission:users.manage')->name('students.store');
    Route::get('/teachers', [UserController::class, 'index'])->defaults('role', 'teacher')->middleware('permission:users.manage')->name('teachers.index');
    Route::post('/teachers', [UserController::class, 'store'])->defaults('role', 'teacher')->middleware('permission:users.manage')->name('teachers.store');
    Route::get('/parents', [UserController::class, 'index'])->defaults('role', 'parent')->middleware('permission:users.manage')->name('parents.index');
    Route::post('/parents', [UserController::class, 'store'])->defaults('role', 'parent')->middleware('permission:users.manage')->name('parents.store');
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->middleware('permission:users.manage')->name('users.status');

    Route::get('/courses', [CourseController::class, 'index'])->middleware('permission:courses.manage')->name('courses.index');
    Route::post('/courses', [CourseController::class, 'store'])->middleware('permission:courses.manage')->name('courses.store');
    Route::patch('/courses/{course}', [CourseController::class, 'update'])->middleware('permission:courses.manage')->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->middleware('permission:courses.manage')->name('courses.destroy');

    Route::get('/classes', [ClassroomController::class, 'index'])->middleware('permission:classes.manage')->name('classes.index');
    Route::post('/classes', [ClassroomController::class, 'store'])->middleware('permission:classes.manage')->name('classes.store');
    Route::patch('/classes/{classroom}', [ClassroomController::class, 'update'])->middleware('permission:classes.manage')->name('classes.update');
    Route::delete('/classes/{classroom}', [ClassroomController::class, 'destroy'])->middleware('permission:classes.manage')->name('classes.destroy');

    Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('permission:attendance.manage.all')->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('permission:attendance.manage.all')->name('attendance.store');

    Route::get('/homework', [HomeworkController::class, 'index'])->middleware('permission:homework.manage.all')->name('homework.index');
    Route::post('/homework', [HomeworkController::class, 'store'])->middleware('permission:homework.manage.all')->name('homework.store');
    Route::patch('/homework-submissions/{submission}/review', [HomeworkController::class, 'review'])->middleware('permission:homework.grade')->name('homework.review');
    Route::get('/homework/{homework}/attachment', [HomeworkController::class, 'downloadAttachment'])->middleware('permission:homework.view.all')->name('homework.attachment');

    Route::get('/exams', [ExamController::class, 'index'])->middleware('permission:exams.manage')->name('exams.index');
    Route::post('/exams', [ExamController::class, 'store'])->middleware('permission:exams.manage')->name('exams.store');

    Route::get('/resources', [LearningResourceController::class, 'index'])->middleware('permission:resources.manage')->name('resources.index');
    Route::post('/resources', [LearningResourceController::class, 'store'])->middleware('permission:resources.manage')->name('resources.store');
    Route::delete('/resources/{resource}', [LearningResourceController::class, 'destroy'])->middleware('permission:resources.manage')->name('resources.destroy');
    Route::get('/resources/{resource}/download', [LearningResourceController::class, 'download'])->middleware('permission:resources.view.all')->name('resources.download');

    Route::get('/consultations', [ConsultationController::class, 'index'])->middleware('permission:consultations.manage')->name('consultations.index');
    Route::patch('/consultations/{consultation}', [ConsultationController::class, 'update'])->middleware('permission:consultations.manage')->name('consultations.update');

    Route::get('/messages', [MessageController::class, 'index'])->middleware('permission:messages.view.all')->name('messages.index');
    Route::post('/messages/{message}/reply', [MessageController::class, 'reply'])->middleware('permission:messages.reply')->name('messages.reply');
    Route::patch('/messages/{message}/read', [MessageController::class, 'markRead'])->middleware('permission:messages.view.all')->name('messages.read');

    Route::get('/announcements', [AnnouncementController::class, 'index'])->middleware('permission:announcements.manage')->name('announcements.index');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('permission:announcements.manage')->name('announcements.store');
    Route::patch('/announcements/{announcement}/status', [AnnouncementController::class, 'updateStatus'])->middleware('permission:announcements.manage')->name('announcements.status');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('permission:announcements.manage')->name('announcements.destroy');

    Route::get('/spotplayer', [SpotPlayerController::class, 'index'])->middleware('permission:integrations.manage')->name('spotplayer.index');
    Route::patch('/spotplayer/settings', [SpotPlayerController::class, 'updateSettings'])->middleware('permission:integrations.manage')->name('spotplayer.settings');
    Route::post('/spotplayer/sync-courses', [SpotPlayerController::class, 'requestCourseSync'])->middleware('permission:integrations.manage')->name('spotplayer.sync-courses');
    Route::post('/spotplayer/videos', [SpotPlayerController::class, 'storeVideo'])->middleware('permission:integrations.manage')->name('spotplayer.videos.store');
    Route::get('/spotplayer/videos/{video}/download', [SpotPlayerController::class, 'downloadStagedVideo'])->middleware('permission:integrations.manage')->name('spotplayer.videos.download');
    Route::post('/spotplayer/licenses', [SpotPlayerController::class, 'requestLicense'])->middleware('permission:integrations.manage')->name('spotplayer.licenses.store');
    Route::post('/spotplayer/licenses/{license}/revoke', [SpotPlayerController::class, 'requestRevocation'])->middleware('permission:integrations.manage')->name('spotplayer.licenses.revoke');

    Route::get('/quiz24', [Quiz24Controller::class, 'index'])->middleware('permission:integrations.manage')->name('quiz24.index');
    Route::patch('/quiz24/settings', [Quiz24Controller::class, 'updateSettings'])->middleware('permission:integrations.manage')->name('quiz24.settings');
    Route::post('/quiz24/sync-exams', [Quiz24Controller::class, 'requestExamSync'])->middleware('permission:integrations.manage')->name('quiz24.sync-exams');
    Route::post('/quiz24/exams/{exam}/sync-results', [Quiz24Controller::class, 'requestResultsSync'])->middleware('permission:integrations.manage')->name('quiz24.sync-results');
    Route::post('/quiz24/unmatched/{participant}/resolve', [Quiz24Controller::class, 'resolveParticipant'])->middleware('permission:integrations.manage')->name('quiz24.unmatched.resolve');
});
