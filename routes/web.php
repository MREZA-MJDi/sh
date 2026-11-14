<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\ConsultationController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseContentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\ExamQuestionController;
use App\Http\Controllers\Admin\ExamAttemptReviewController;
use App\Http\Controllers\Admin\HomeworkController;
use App\Http\Controllers\Admin\LearningResourceController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\Quiz24Controller;
use App\Http\Controllers\Admin\SpotPlayerController;
use App\Http\Controllers\Admin\SeoDashboardController;
use App\Http\Controllers\SeoPublicController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Guardian\DashboardController as GuardianDashboardController;
use App\Http\Controllers\Guardian\ConsultationController as GuardianConsultationController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use App\Http\Controllers\Student\NoteController;
use App\Http\Controllers\Student\VideoController as StudentVideoController;
use App\Http\Controllers\Student\HomeworkSubmissionController as StudentHomeworkSubmissionController;
use App\Http\Controllers\Portal\MessageController as PortalMessageController;
use App\Http\Controllers\Portal\LearningResourceController as PortalLearningResourceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/courses', [PublicSiteController::class, 'courses'])->name('public.courses.index');
Route::get('/teachers', [PublicSiteController::class, 'teachers'])->name('public.teachers.index');
Route::get('/teachers/{teacher}', [PublicSiteController::class, 'teacher'])->name('public.teachers.show');
Route::get('/contact', [PublicSiteController::class, 'contact'])->name('public.contact');
Route::post('/contact', [PublicSiteController::class, 'storeContact'])->middleware('throttle:5,1')->name('public.contact.store');
Route::get('/cart', [PublicSiteController::class, 'cart'])->name('public.cart');
Route::post('/cart/{course}/add', [PublicSiteController::class, 'addToCart'])->name('public.cart.add');
Route::delete('/cart/{course}', [PublicSiteController::class, 'removeFromCart'])->name('public.cart.remove');
Route::get('/courses/{slug}', [PublicSiteController::class, 'course'])->name('public.courses.show');
Route::get('/articles', [SeoPublicController::class, 'index'])->name('seo.articles.index');
Route::get('/articles/{slug}', [SeoPublicController::class, 'show'])->name('seo.articles.show');
Route::get('/sitemap.xml', [SeoPublicController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoPublicController::class, 'robots'])->name('seo.robots');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');

    Route::get('/register', [SessionController::class, 'register'])->name('register');
    Route::post('/register', [SessionController::class, 'storeRegistration'])
        ->middleware('throttle:3,1')
        ->name('register.store');

    // Backward-compatible admin login URL; authentication is now role-aware.
    Route::get('/admin/login', fn () => redirect()->route('login'))->name('admin.login');
    Route::post('/admin/login', [SessionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('admin.login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'role:admin,teacher,seo'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::get('/', [AdminDashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');

    Route::get('/contact-messages', [ContactMessageController::class, 'index'])->middleware('permission:messages.view.all')->name('contact-messages.index');

    Route::get('/seo', [SeoDashboardController::class, 'index'])->middleware('permission:seo.manage')->name('seo.index');
    Route::patch('/seo/settings', [SeoDashboardController::class, 'updateSettings'])->middleware('permission:seo.manage')->name('seo.settings.update');
    Route::get('/seo/banners', [SeoDashboardController::class, 'banners'])->middleware('permission:seo.manage')->name('seo.banners.index');
    Route::post('/seo/banners', [SeoDashboardController::class, 'storeBanner'])->middleware('permission:seo.manage')->name('seo.banners.store');
    Route::patch('/seo/banners/{banner}', [SeoDashboardController::class, 'updateBanner'])->middleware('permission:seo.manage')->name('seo.banners.update');
    Route::delete('/seo/banners/{banner}', [SeoDashboardController::class, 'destroyBanner'])->middleware('permission:seo.manage')->name('seo.banners.destroy');
    Route::get('/seo/articles', [SeoDashboardController::class, 'articles'])->middleware('permission:seo.manage')->name('seo.articles.index');
    Route::post('/seo/articles', [SeoDashboardController::class, 'storeArticle'])->middleware('permission:seo.manage')->name('seo.articles.store');
    Route::patch('/seo/articles/{article}', [SeoDashboardController::class, 'updateArticle'])->middleware('permission:seo.manage')->name('seo.articles.update');
    Route::delete('/seo/articles/{article}', [SeoDashboardController::class, 'destroyArticle'])->middleware('permission:seo.manage')->name('seo.articles.destroy');

    Route::get('/lookups/students', [LookupController::class, 'students'])->middleware(['permission:users.manage', 'throttle:60,1'])->name('lookups.students');
    Route::get('/students', [UserController::class, 'index'])->defaults('role', 'student')->middleware('permission:users.manage,students.view.assigned')->name('students.index');
    Route::post('/students', [UserController::class, 'store'])->defaults('role', 'student')->middleware('permission:users.manage')->name('students.store');
    Route::get('/teachers', [UserController::class, 'index'])->defaults('role', 'teacher')->middleware('permission:users.manage')->name('teachers.index');
    Route::post('/teachers', [UserController::class, 'store'])->defaults('role', 'teacher')->middleware('permission:users.manage')->name('teachers.store');
    Route::patch('/teachers/{teacher}', [UserController::class, 'updateTeacher'])->middleware('permission:users.manage')->name('teachers.update');
    Route::get('/parents', [UserController::class, 'index'])->defaults('role', 'parent')->middleware('permission:users.manage')->name('parents.index');
    Route::post('/parents', [UserController::class, 'store'])->defaults('role', 'parent')->middleware('permission:users.manage')->name('parents.store');
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->middleware('permission:users.manage')->name('users.status');

    Route::get('/courses', [CourseController::class, 'index'])->middleware('permission:courses.manage,courses.view.assigned')->name('courses.index');
    Route::post('/courses', [CourseController::class, 'store'])->middleware('permission:courses.manage')->name('courses.store');
    Route::patch('/courses/{course}', [CourseController::class, 'update'])->middleware('permission:courses.manage')->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->middleware('permission:courses.manage')->name('courses.destroy');
    Route::middleware('permission:courses.manage,courses.content.manage.assigned')->group(function (): void {
        Route::get('/courses/{course}/content', [CourseContentController::class, 'show'])->name('courses.content');
        Route::post('/courses/{course}/sections', [CourseContentController::class, 'storeSection'])->name('courses.sections.store');
        Route::patch('/course-sections/{section}', [CourseContentController::class, 'updateSection'])->name('courses.sections.update');
        Route::delete('/course-sections/{section}', [CourseContentController::class, 'destroySection'])->name('courses.sections.destroy');
        Route::post('/course-sections/{section}/lessons', [CourseContentController::class, 'storeLesson'])->name('courses.lessons.store');
        Route::patch('/lessons/{lesson}', [CourseContentController::class, 'updateLesson'])->name('courses.lessons.update');
        Route::delete('/lessons/{lesson}', [CourseContentController::class, 'destroyLesson'])->name('courses.lessons.destroy');
    });

    Route::get('/classes', [ClassroomController::class, 'index'])->middleware('permission:classes.manage,classes.view.assigned,classes.manage.assigned')->name('classes.index');
    Route::post('/classes', [ClassroomController::class, 'store'])->middleware('permission:classes.manage,classes.manage.assigned')->name('classes.store');
    Route::patch('/classes/{classroom}', [ClassroomController::class, 'update'])->middleware('permission:classes.manage,classes.manage.assigned')->name('classes.update');
    Route::delete('/classes/{classroom}', [ClassroomController::class, 'destroy'])->middleware('permission:classes.manage,classes.manage.assigned')->name('classes.destroy');

    Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('permission:attendance.manage.all,attendance.manage.assigned,attendance.view.assigned')->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('permission:attendance.manage.all,attendance.manage.assigned')->name('attendance.store');

    Route::get('/homework', [HomeworkController::class, 'index'])->middleware('permission:homework.manage.all,homework.manage.assigned,homework.view.assigned')->name('homework.index');
    Route::post('/homework', [HomeworkController::class, 'store'])->middleware('permission:homework.manage.all,homework.manage.assigned')->name('homework.store');
    Route::patch('/homework-submissions/{submission}/review', [HomeworkController::class, 'review'])->middleware('permission:homework.grade')->name('homework.review');
    Route::get('/homework/{homework}/attachment', [HomeworkController::class, 'downloadAttachment'])->middleware('permission:homework.view.all,homework.view.assigned')->name('homework.attachment');

    Route::get('/exams', [ExamController::class, 'index'])->middleware('permission:exams.manage,exams.manage.assigned')->name('exams.index');
    Route::post('/exams', [ExamController::class, 'store'])->middleware('permission:exams.manage,exams.manage.assigned')->name('exams.store');
    Route::patch('/exams/{exam}', [ExamController::class, 'update'])->middleware('permission:exams.manage,exams.manage.assigned')->name('exams.update');
    Route::get('/exams/{exam}/results', [ExamController::class, 'results'])->middleware('permission:exams.manage,exams.manage.assigned')->name('exams.results');
    Route::middleware('permission:exams.manage,exams.manage.assigned')->group(function (): void {
        Route::get('/exams/{exam}/questions', [ExamQuestionController::class, 'index'])->name('exams.questions');
        Route::post('/exams/{exam}/questions', [ExamQuestionController::class, 'store'])->name('exams.questions.store');
        Route::patch('/exams/{exam}/questions/{question}', [ExamQuestionController::class, 'update'])->name('exams.questions.update');
        Route::delete('/exams/{exam}/questions/{question}', [ExamQuestionController::class, 'destroy'])->name('exams.questions.destroy');
        Route::get('/exams/{exam}/attempts', [ExamAttemptReviewController::class, 'index'])->name('exams.attempts');
        Route::patch('/exams/{exam}/attempts/{attempt}/grade', [ExamAttemptReviewController::class, 'grade'])->name('exams.attempts.grade');
    });

    Route::get('/resources', [LearningResourceController::class, 'index'])->middleware('permission:resources.manage,resources.manage.assigned')->name('resources.index');
    Route::post('/resources', [LearningResourceController::class, 'store'])->middleware('permission:resources.manage,resources.manage.assigned')->name('resources.store');
    Route::patch('/resources/{resource}', [LearningResourceController::class, 'update'])->middleware('permission:resources.manage,resources.manage.assigned')->name('resources.update');
    Route::delete('/resources/{resource}', [LearningResourceController::class, 'destroy'])->middleware('permission:resources.manage,resources.manage.assigned')->name('resources.destroy');
    Route::get('/resources/{resource}/download', [LearningResourceController::class, 'download'])->middleware('permission:resources.view.all,resources.manage.assigned')->name('resources.download');

    Route::get('/consultations', [ConsultationController::class, 'index'])->middleware('permission:consultations.manage')->name('consultations.index');
    Route::patch('/consultations/{consultation}', [ConsultationController::class, 'update'])->middleware('permission:consultations.manage')->name('consultations.update');

    Route::get('/messages', [MessageController::class, 'index'])->middleware('permission:messages.view.all,messages.view.assigned')->name('messages.index');
    Route::post('/messages/{message}/reply', [MessageController::class, 'reply'])->middleware('permission:messages.reply')->name('messages.reply');
    Route::patch('/messages/{message}/read', [MessageController::class, 'markRead'])->middleware('permission:messages.view.all,messages.view.assigned')->name('messages.read');

    Route::get('/announcements', [AnnouncementController::class, 'index'])->middleware('permission:announcements.manage,announcements.view')->name('announcements.index');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('permission:announcements.manage')->name('announcements.store');
    Route::patch('/announcements/{announcement}/status', [AnnouncementController::class, 'updateStatus'])->middleware('permission:announcements.manage')->name('announcements.status');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('permission:announcements.manage')->name('announcements.destroy');

    Route::get('/spotplayer', [SpotPlayerController::class, 'index'])->middleware('permission:integrations.manage,videos.manage.assigned')->name('spotplayer.index');
    Route::patch('/spotplayer/settings', [SpotPlayerController::class, 'updateSettings'])->middleware('permission:integrations.manage')->name('spotplayer.settings');
    Route::post('/spotplayer/sync-courses', [SpotPlayerController::class, 'requestCourseSync'])->middleware('permission:integrations.manage')->name('spotplayer.sync-courses');
    Route::post('/spotplayer/videos', [SpotPlayerController::class, 'storeVideo'])->middleware('permission:integrations.manage,videos.manage.assigned')->name('spotplayer.videos.store');
    Route::get('/spotplayer/videos/{video}/download', [SpotPlayerController::class, 'downloadStagedVideo'])->middleware('permission:integrations.manage,videos.manage.assigned')->name('spotplayer.videos.download');
    Route::post('/spotplayer/licenses', [SpotPlayerController::class, 'requestLicense'])->middleware('permission:integrations.manage')->name('spotplayer.licenses.store');
    Route::post('/spotplayer/licenses/{license}/revoke', [SpotPlayerController::class, 'requestRevocation'])->middleware('permission:integrations.manage')->name('spotplayer.licenses.revoke');

    Route::get('/quiz24', [Quiz24Controller::class, 'index'])->middleware('permission:integrations.manage')->name('quiz24.index');
    Route::patch('/quiz24/settings', [Quiz24Controller::class, 'updateSettings'])->middleware('permission:integrations.manage')->name('quiz24.settings');
    Route::post('/quiz24/sync-exams', [Quiz24Controller::class, 'requestExamSync'])->middleware('permission:integrations.manage')->name('quiz24.sync-exams');
    Route::post('/quiz24/exams/{exam}/sync-results', [Quiz24Controller::class, 'requestResultsSync'])->middleware('permission:integrations.manage')->name('quiz24.sync-results');
    Route::post('/quiz24/unmatched/{participant}/resolve', [Quiz24Controller::class, 'resolveParticipant'])->middleware('permission:integrations.manage')->name('quiz24.unmatched.resolve');
});

Route::middleware(['auth', 'role:student', 'permission:dashboard.view'])
    ->prefix('student')
    ->name('student.')
    ->group(function (): void {
        Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');

        Route::middleware('permission:exams.view.own')->group(function (): void {
            Route::get('/exams', [StudentExamController::class, 'index'])->name('exams.index');
            Route::get('/exams/{exam}', [StudentExamController::class, 'show'])->name('exams.show');
            Route::get('/exams/{exam}/attempts/{attempt}', [StudentExamController::class, 'attempt'])->name('exams.attempt');
            Route::get('/exams/{exam}/result', [StudentExamController::class, 'result'])->name('exams.result');
        });
        Route::post('/exams/{exam}/start', [StudentExamController::class, 'start'])->middleware('permission:exams.attempt')->name('exams.start');
        Route::post('/exam-attempts/{attempt}/submit', [StudentExamController::class, 'submit'])->middleware('permission:exams.attempt')->name('exam-attempts.submit');
        Route::middleware('permission:videos.view.enrolled')->group(function (): void {
            Route::get('/videos', [StudentVideoController::class, 'index'])->name('videos.index');
            Route::get('/videos/{video}', [StudentVideoController::class, 'show'])->name('videos.show');
            Route::get('/videos/{video}/stream', [StudentVideoController::class, 'stream'])->name('videos.stream');
        });

        Route::middleware('permission:lessons.view')->group(function (): void {
            Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/{course}', [StudentCourseController::class, 'show'])->name('courses.show');
            Route::patch('/courses/{course}/lessons/{lesson}/progress', [StudentCourseController::class, 'updateProgress'])
                ->middleware('permission:lessons.progress')->name('courses.lessons.progress');
        });
        Route::get('/resources/{resource}/download', [PortalLearningResourceController::class, 'download'])->middleware('permission:resources.view.enrolled')->name('resources.download');

        Route::middleware('permission:homework.submit')->group(function (): void {
            Route::post('/homework/{homework}/submissions', [StudentHomeworkSubmissionController::class, 'store'])->name('homework.submissions.store');
            Route::delete('/homework/{homework}/submissions', [StudentHomeworkSubmissionController::class, 'destroy'])->name('homework.submissions.destroy');
            Route::get('/homework-submission-files/{file}/download', [StudentHomeworkSubmissionController::class, 'downloadFile'])->name('homework.submission-files.download');
            Route::delete('/homework-submission-files/{file}', [StudentHomeworkSubmissionController::class, 'destroyFile'])->name('homework.submission-files.destroy');
        });
        Route::middleware('permission:notes.manage.own')->group(function (): void {
            Route::get('/notes', [NoteController::class, 'index'])->name('notes.index');
            Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
            Route::patch('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
            Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
        });
    });

Route::middleware(['auth', 'role:teacher', 'permission:dashboard.view'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function (): void {
        // Compatibility URL only: teachers use the exact same admin dashboard, layout and views.
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    });

Route::middleware(['auth', 'role:parent', 'permission:dashboard.view'])
    ->prefix('parent')
    ->name('guardian.')
    ->group(function (): void {
        Route::get('/', [GuardianDashboardController::class, 'index'])->name('dashboard');
        Route::get('/resources/{resource}/download', [PortalLearningResourceController::class, 'download'])->middleware('permission:resources.view.enrolled')->name('resources.download');
    });

Route::middleware(['auth', 'role:parent', 'permission:consultations.create'])
    ->prefix('parent/consultations')
    ->name('guardian.consultations.')
    ->group(function (): void {
        Route::get('/', [GuardianConsultationController::class, 'index'])->name('index');
        Route::get('/create', [GuardianConsultationController::class, 'create'])->name('create');
        Route::post('/', [GuardianConsultationController::class, 'store'])->name('store');
        Route::get('/{consultation}', [GuardianConsultationController::class, 'show'])->name('show');
        Route::post('/{consultation}/replies', [GuardianConsultationController::class, 'reply'])->name('reply');
        Route::delete('/{consultation}', [GuardianConsultationController::class, 'destroy'])->name('destroy');
    });


Route::middleware(['auth', 'role:student'])
    ->prefix('student/messages')
    ->name('student.messages.')
    ->group(function (): void {
        Route::get('/', [PortalMessageController::class, 'index'])->middleware('permission:messages.view.own')->name('index');
        Route::get('/create', [PortalMessageController::class, 'create'])->middleware('permission:messages.send.own')->name('create');
        Route::post('/', [PortalMessageController::class, 'store'])->middleware('permission:messages.send.own')->name('store');
        Route::get('/{message}', [PortalMessageController::class, 'show'])->middleware('permission:messages.view.own')->name('show');
        Route::patch('/{message}', [PortalMessageController::class, 'update'])->middleware('permission:messages.send.own')->name('update');
        Route::post('/{message}/replies', [PortalMessageController::class, 'reply'])->middleware('permission:messages.send.own')->name('reply');
        Route::delete('/{message}', [PortalMessageController::class, 'destroy'])->middleware('permission:messages.send.own')->name('destroy');
    });

Route::middleware(['auth', 'role:parent'])
    ->prefix('parent/messages')
    ->name('guardian.messages.')
    ->group(function (): void {
        Route::get('/', [PortalMessageController::class, 'index'])->middleware('permission:messages.view.children')->name('index');
        Route::get('/create', [PortalMessageController::class, 'create'])->middleware('permission:messages.send.own')->name('create');
        Route::post('/', [PortalMessageController::class, 'store'])->middleware('permission:messages.send.own')->name('store');
        Route::get('/{message}', [PortalMessageController::class, 'show'])->middleware('permission:messages.view.children')->name('show');
        Route::patch('/{message}', [PortalMessageController::class, 'update'])->middleware('permission:messages.send.own')->name('update');
        Route::post('/{message}/replies', [PortalMessageController::class, 'reply'])->middleware('permission:messages.send.own')->name('reply');
        Route::delete('/{message}', [PortalMessageController::class, 'destroy'])->middleware('permission:messages.send.own')->name('destroy');
    });
