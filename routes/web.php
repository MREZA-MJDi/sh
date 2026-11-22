<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CommunicationCenterController;
use App\Http\Controllers\Admin\ConsultationController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\EnrollmentAdminController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\OnlineClassController;
use App\Http\Controllers\Admin\ChunkUploadController;
use App\Http\Controllers\Admin\LearningAdminController;
use App\Http\Controllers\Admin\CourseContentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\ExamQuestionController;
use App\Http\Controllers\Admin\ExamAttemptReviewController;
use App\Http\Controllers\Admin\HomeworkController;
use App\Http\Controllers\Admin\LearningResourceController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\ParentMeetingController as AdminParentMeetingController;
use App\Http\Controllers\Admin\Quiz24Controller;
use App\Http\Controllers\Admin\SpotPlayerController;
use App\Http\Controllers\Admin\SeoDashboardController;
use App\Http\Controllers\Admin\HomeQuizQuestionController;
use App\Http\Controllers\SeoPublicController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\PublicHomeQuizController;
use App\Http\Controllers\PublicLearningResourceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Guardian\DashboardController as GuardianDashboardController;
use App\Http\Controllers\Guardian\ConsultationController as GuardianConsultationController;
use App\Http\Controllers\Guardian\ParentMeetingController as GuardianParentMeetingController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ParentMeetingController as StudentParentMeetingController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use App\Http\Controllers\Student\NoteController;
use App\Http\Controllers\Student\HomeworkSubmissionController as StudentHomeworkSubmissionController;
use App\Http\Controllers\Portal\MessageController as PortalMessageController;
use App\Http\Controllers\Teacher\MessageController as TeacherMessageController;
use App\Http\Controllers\Teacher\ConsultationController as TeacherConsultationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AnnouncementReadController;
use App\Http\Controllers\Teacher\PortalController as TeacherPortalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/home-quiz/question/{step}', [PublicHomeQuizController::class, 'question'])
    ->whereNumber('step')->middleware('throttle:30,1')->name('public.home.quiz.question');
Route::post('/home-quiz/answer', [PublicHomeQuizController::class, 'answer'])
    ->middleware('throttle:30,1')->name('public.home.quiz.answer');
Route::get('/courses', [PublicSiteController::class, 'courses'])->name('public.courses.index');
Route::get('/teachers', [PublicSiteController::class, 'teachers'])->name('public.teachers.index');
Route::get('/teachers/{teacher}', [PublicSiteController::class, 'teacher'])->name('public.teachers.show');
Route::get('/contact', [PublicSiteController::class, 'contact'])->name('public.contact');
Route::post('/contact', [PublicSiteController::class, 'storeContact'])->middleware('throttle:5,1')->name('public.contact.store');
Route::get('/cart', [PublicSiteController::class, 'cart'])->name('public.cart');
Route::post('/cart/{course}/add', [PublicSiteController::class, 'addToCart'])->name('public.cart.add');
Route::delete('/cart/{course}', [PublicSiteController::class, 'removeFromCart'])->name('public.cart.remove');
Route::get('/courses/{slug}', [PublicSiteController::class, 'course'])->name('public.courses.show');
Route::get('/courses/{slug}/videos/{video}/play', [PublicSiteController::class, 'playCourseVideo'])
    ->middleware('auth')->whereNumber('video')->name('public.courses.videos.play');
Route::get('/resources/{resource}/download', [PublicLearningResourceController::class, 'download'])->middleware('throttle:20,1')->name('public.resources.download');
Route::get('/articles', [SeoPublicController::class, 'index'])->name('seo.articles.index');
Route::get('/articles/{slug}', [SeoPublicController::class, 'show'])->name('seo.articles.show');
Route::get('/sitemap.xml', [SeoPublicController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/sitemap/{type}.xml', [SeoPublicController::class, 'sitemapSection'])
    ->whereIn('type', ['static', 'courses', 'teachers', 'articles'])
    ->name('seo.sitemap.section');
Route::get('/robots.txt', [SeoPublicController::class, 'robots'])->name('seo.robots');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');

    // Production demo credentials require a separate session unlock secret.
    Route::post('/login/demo-access', [SessionController::class, 'unlockDemoAccounts'])
        ->middleware('throttle:3,1')
        ->name('login.demo.unlock');

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
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('/announcements/{announcement}/read', [AnnouncementReadController::class, 'store'])->name('announcements.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/profile/avatar', function (Request $request) {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192']]);
        $user = $request->user();
        $previous = $user->avatar_path;
        $path = $request->file('avatar')->store('avatars', 'public');
        abort_unless($path, 500, 'ذخیره تصویر پروفایل انجام نشد.');
        $user->forceFill(['avatar_path' => $path])->save();
        if ($previous && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }
        return back()->with('success', 'تصویر پروفایل با موفقیت به‌روزرسانی شد.');
    })->middleware('throttle:10,1')->name('profile.avatar.update');
});

Route::middleware(['auth', 'role:admin,teacher,seo'])->prefix('admin')->name('admin.')->group(function (): void {
    // The admin route group prefixes this as admin.logout; the global logout route remains role-neutral.
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::get('/', [AdminDashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');

    // Backward-compatible URLs for the retired demo screens. Keep bookmarks working
    // while all data and actions live in the canonical workflows.
    Route::get('/assignments', fn () => auth()->user()->hasRole('teacher') ? redirect()->route('teacher.homework') : redirect()->route('admin.homework.index'))
        ->middleware('permission:homework.manage.all,homework.manage.assigned,homework.view.assigned')
        ->name('assignments.legacy');
    Route::get('/recordings', fn () => auth()->user()->hasRole('teacher') ? redirect()->route('teacher.videos') : redirect()->route('admin.spotplayer.index'))
        ->middleware('permission:integrations.manage,videos.manage.assigned')
        ->name('recordings.legacy');
    Route::get('/meetings', fn () => auth()->user()->hasRole('teacher') ? redirect()->route('teacher.meetings') : redirect()->route('admin.parent-meetings.index'))
        ->middleware('permission:classes.view.assigned,classes.manage.assigned,classes.manage')
        ->name('meetings.legacy');
    Route::get('/parent-messages', fn () => auth()->user()->hasRole('teacher') ? redirect()->route('teacher.messages.index') : redirect()->route('admin.messages.index'))
        ->middleware('permission:messages.view.all,messages.view.assigned')
        ->name('parent-messages.legacy');

    Route::get('/communication-center', [CommunicationCenterController::class, 'index'])
        ->middleware('permission:messages.view.all,messages.view.assigned,consultations.manage,classes.manage,classes.manage.assigned')
        ->name('communication-center.index');

    Route::get('/contact-messages', [ContactMessageController::class, 'index'])->middleware('permission:messages.view.all')->name('contact-messages.index');
    Route::patch('/contact-messages/{message}/status', [ContactMessageController::class, 'updateStatus'])->middleware('permission:messages.view.all')->name('contact-messages.status');
    Route::delete('/contact-messages/{message}', [ContactMessageController::class, 'destroy'])->middleware('permission:messages.view.all')->name('contact-messages.destroy');

    Route::get('/seo', [SeoDashboardController::class, 'index'])->middleware('permission:seo.manage')->name('seo.index');
    Route::patch('/seo/settings', [SeoDashboardController::class, 'updateSettings'])->middleware('permission:seo.manage')->name('seo.settings.update');
    Route::get('/seo/banners', [SeoDashboardController::class, 'banners'])->middleware('permission:seo.manage')->name('seo.banners.index');
    Route::post('/seo/banners', [SeoDashboardController::class, 'storeBanner'])->middleware('permission:seo.manage')->name('seo.banners.store');
    Route::patch('/seo/banners/{banner}', [SeoDashboardController::class, 'updateBanner'])->middleware('permission:seo.manage')->name('seo.banners.update');
    Route::delete('/seo/banners/{banner}', [SeoDashboardController::class, 'destroyBanner'])->middleware('permission:seo.manage')->name('seo.banners.destroy');
    Route::get('/seo/articles', [SeoDashboardController::class, 'articles'])->middleware('permission:seo.manage')->name('seo.articles.index');
    Route::get('/seo/quiz', [HomeQuizQuestionController::class, 'index'])->middleware('permission:seo.manage')->name('seo.quiz.index');
    Route::post('/seo/quiz', [HomeQuizQuestionController::class, 'store'])->middleware('permission:seo.manage')->name('seo.quiz.store');
    Route::patch('/seo/quiz/{question}', [HomeQuizQuestionController::class, 'update'])->middleware('permission:seo.manage')->name('seo.quiz.update');
    Route::delete('/seo/quiz/{question}', [HomeQuizQuestionController::class, 'destroy'])->middleware('permission:seo.manage')->name('seo.quiz.destroy');
    Route::get('/seo/testimonials', [SeoDashboardController::class, 'testimonials'])->middleware('permission:seo.manage')->name('seo.testimonials.index');
    Route::post('/seo/testimonials', [SeoDashboardController::class, 'storeTestimonial'])->middleware('permission:seo.manage')->name('seo.testimonials.store');
    Route::patch('/seo/testimonials/{testimonial}', [SeoDashboardController::class, 'updateTestimonial'])->middleware('permission:seo.manage')->name('seo.testimonials.update');
    Route::delete('/seo/testimonials/{testimonial}', [SeoDashboardController::class, 'destroyTestimonial'])->middleware('permission:seo.manage')->name('seo.testimonials.destroy');
    Route::post('/seo/articles', [SeoDashboardController::class, 'storeArticle'])->middleware('permission:seo.manage')->name('seo.articles.store');
    Route::patch('/seo/articles/{article}', [SeoDashboardController::class, 'updateArticle'])->middleware('permission:seo.manage')->name('seo.articles.update');
    Route::delete('/seo/articles/{article}', [SeoDashboardController::class, 'destroyArticle'])->middleware('permission:seo.manage')->name('seo.articles.destroy');

    Route::get('/lookups/students', [LookupController::class, 'students'])->middleware(['permission:users.manage', 'throttle:60,1'])->name('lookups.students');
    Route::get('/students', [UserController::class, 'index'])->defaults('role', 'student')->middleware('permission:users.manage,students.view.assigned')->name('students.index');
    Route::post('/students', [UserController::class, 'store'])->defaults('role', 'student')->middleware('permission:users.manage')->name('students.store');
    Route::patch('/students/{student}', [UserController::class, 'updateStudent'])->middleware('permission:users.manage')->name('students.update');
    Route::get('/teachers', [UserController::class, 'index'])->defaults('role', 'teacher')->middleware('permission:users.manage')->name('teachers.index');
    Route::post('/teachers', [UserController::class, 'store'])->defaults('role', 'teacher')->middleware('permission:users.manage')->name('teachers.store');
    Route::patch('/teachers/{teacher}', [UserController::class, 'updateTeacher'])->middleware('permission:users.manage')->name('teachers.update');
    Route::get('/parents', [UserController::class, 'index'])->defaults('role', 'parent')->middleware('permission:users.manage')->name('parents.index');
    Route::post('/parents', [UserController::class, 'store'])->defaults('role', 'parent')->middleware('permission:users.manage')->name('parents.store');
    Route::patch('/parents/{parent}', [UserController::class, 'updateParent'])->middleware('permission:users.manage')->name('parents.update');
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->middleware('permission:users.manage')->name('users.status');

    Route::get('/enrollments', [EnrollmentAdminController::class, 'index'])->middleware('permission:users.manage')->name('enrollments.index');
    Route::post('/enrollments', [EnrollmentAdminController::class, 'store'])->middleware('permission:users.manage')->name('enrollments.store');
    Route::patch('/enrollments/{enrollment}/revoke', [EnrollmentAdminController::class, 'revoke'])->middleware('permission:users.manage')->name('enrollments.revoke');
    Route::get('/payments', [PaymentController::class, 'index'])->middleware('permission:users.manage')->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'storeManual'])->middleware('permission:users.manage')->name('payments.store');
    Route::patch('/payments/{payment}/verify', [PaymentController::class, 'verifyManual'])->middleware('permission:users.manage')->name('payments.verify');

    Route::get('/courses/create', [CourseController::class, 'create'])->middleware('permission:courses.manage')->name('courses.create');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->middleware('permission:courses.manage')->name('courses.edit');
    Route::get('/courses', [CourseController::class, 'index'])->middleware('permission:courses.manage,courses.view.assigned')->name('courses.index');
    Route::post('/courses', [CourseController::class, 'store'])->middleware('permission:courses.manage')->name('courses.store');
    Route::get('/courses/{course}/teaser', [LearningAdminController::class, 'teaser'])->middleware('permission:courses.manage')->name('courses.teaser');
    Route::get('/courses/{course}/teaser/status', [LearningAdminController::class, 'teaserStatus'])->middleware('permission:courses.manage')->name('courses.teaser.status');
    Route::delete('/courses/{course}/teaser', [LearningAdminController::class, 'destroyTeaser'])->middleware('permission:courses.manage')->name('courses.teaser.destroy');
    Route::post('/uploads/start', [ChunkUploadController::class, 'start'])->middleware(['permission:courses.manage,integrations.manage,videos.manage.assigned', 'throttle:upload-start'])->name('uploads.start');
    Route::post('/uploads/resume', [ChunkUploadController::class, 'resume'])->middleware(['permission:courses.manage,integrations.manage,videos.manage.assigned', 'throttle:upload-resume'])->name('uploads.resume');
    Route::post('/uploads/status', [ChunkUploadController::class, 'status'])->middleware(['permission:courses.manage,integrations.manage,videos.manage.assigned', 'throttle:upload-status'])->name('uploads.status');
    Route::post('/uploads/chunk', [ChunkUploadController::class, 'chunk'])->middleware(['permission:courses.manage,integrations.manage,videos.manage.assigned', 'throttle:upload-chunk'])->name('uploads.chunk');
    Route::post('/uploads/complete', [ChunkUploadController::class, 'complete'])->middleware(['permission:courses.manage,integrations.manage,videos.manage.assigned', 'throttle:upload-complete'])->name('uploads.complete');
    Route::post('/uploads/cancel', [ChunkUploadController::class, 'cancel'])->middleware(['permission:courses.manage,integrations.manage,videos.manage.assigned', 'throttle:upload-cancel'])->name('uploads.cancel');

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
    Route::get('/online-classes', [OnlineClassController::class, 'index'])->middleware('permission:classes.manage,classes.view.assigned,classes.manage.assigned')->name('online-classes.index');
    Route::post('/classes', [ClassroomController::class, 'store'])->middleware('permission:classes.manage,classes.manage.assigned')->name('classes.store');
    Route::post('/classes/{classroom}/skyroom-room', [ClassroomController::class, 'ensureSkyroomRoom'])->middleware('permission:classes.manage,classes.manage.assigned')->name('classes.skyroom-room');
    Route::patch('/classes/{classroom}', [ClassroomController::class, 'update'])->middleware('permission:classes.manage,classes.manage.assigned')->name('classes.update');
    Route::delete('/classes/{classroom}', [ClassroomController::class, 'destroy'])->middleware('permission:classes.manage,classes.manage.assigned')->name('classes.destroy');
    Route::get('/classes/{classroom}/sessions', [ClassSessionController::class, 'index'])->middleware('permission:classes.manage,classes.view.assigned,classes.manage.assigned')->name('classes.sessions.index');
    Route::post('/classes/{classroom}/sessions', [ClassSessionController::class, 'store'])->middleware('permission:classes.manage,classes.manage.assigned')->name('classes.sessions.store');
    Route::patch('/class-sessions/{classSession}', [ClassSessionController::class, 'update'])->middleware('permission:classes.manage,classes.manage.assigned')->name('classes.sessions.update');
    Route::delete('/class-sessions/{classSession}', [ClassSessionController::class, 'destroy'])->middleware('permission:classes.manage,classes.manage.assigned')->name('classes.sessions.destroy');

    Route::get('/parent-meetings/lookup', [AdminParentMeetingController::class, 'lookup'])->middleware(['permission:classes.manage,classes.manage.assigned', 'throttle:30,1'])->name('parent-meetings.lookup');
    Route::get('/parent-meetings', [AdminParentMeetingController::class, 'index'])->middleware('permission:classes.manage,classes.manage.assigned')->name('parent-meetings.index');
    Route::post('/parent-meetings', [AdminParentMeetingController::class, 'store'])->middleware('permission:classes.manage,classes.manage.assigned')->name('parent-meetings.store');
    Route::patch('/parent-meetings/{meeting}/status', [AdminParentMeetingController::class, 'updateStatus'])->middleware('permission:classes.manage,classes.manage.assigned')->name('parent-meetings.status');

    Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('permission:attendance.manage.all,attendance.manage.assigned,attendance.view.assigned')->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('permission:attendance.manage.all,attendance.manage.assigned')->name('attendance.store');
    Route::post('/attendance/{classroom}/{student}/notify', [AttendanceController::class, 'notifyAbsence'])->middleware('permission:attendance.manage.all,attendance.manage.assigned')->name('attendance.notify');

    Route::get('/homework', [HomeworkController::class, 'index'])->middleware(['role:admin', 'permission:homework.manage.all,homework.manage.assigned,homework.view.assigned'])->name('homework.index');
    Route::post('/homework', [HomeworkController::class, 'store'])->middleware(['role:admin', 'permission:homework.manage.all'])->name('homework.store');
    Route::patch('/homework/{homework}', [HomeworkController::class, 'update'])->middleware(['role:admin', 'permission:homework.manage.all'])->name('homework.update');
    Route::delete('/homework/{homework}', [HomeworkController::class, 'destroy'])->middleware(['role:admin', 'permission:homework.manage.all'])->name('homework.destroy');
    Route::patch('/homework-submissions/{submission}/review', [HomeworkController::class, 'review'])->middleware(['role:admin', 'permission:homework.grade'])->name('homework.review');
    Route::get('/homework-submission-files/{file}/download', [HomeworkController::class, 'downloadSubmissionFile'])->middleware('permission:homework.view.all,homework.view.assigned')->name('homework-submission-files.download');
    Route::get('/homework/{homework}/attachment', [HomeworkController::class, 'downloadAttachment'])->middleware('permission:homework.view.all,homework.view.assigned')->name('homework.attachment');

    Route::get('/exams', [ExamController::class, 'index'])->middleware('permission:exams.manage,exams.manage.assigned')->name('exams.index');
    Route::post('/exams', [ExamController::class, 'store'])->middleware('permission:exams.manage,exams.manage.assigned')->name('exams.store');
    Route::patch('/exams/{exam}', [ExamController::class, 'update'])->middleware('permission:exams.manage,exams.manage.assigned')->name('exams.update');
    Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->middleware('permission:exams.manage')->name('exams.destroy');
    Route::get('/exams/{exam}/results', [ExamController::class, 'results'])->middleware('permission:exams.manage,exams.manage.assigned')->name('exams.results');
    Route::middleware('permission:exams.manage,exams.manage.assigned')->group(function (): void {
        Route::get('/exams/{exam}/questions', [ExamQuestionController::class, 'index'])->name('exams.questions');
        Route::post('/exams/{exam}/questions', [ExamQuestionController::class, 'store'])->name('exams.questions.store');
        Route::patch('/exams/{exam}/questions/{question}', [ExamQuestionController::class, 'update'])->name('exams.questions.update');
        Route::delete('/exams/{exam}/questions/{question}', [ExamQuestionController::class, 'destroy'])->name('exams.questions.destroy');
        Route::get('/exams/{exam}/attempts', [ExamAttemptReviewController::class, 'index'])->name('exams.attempts');
        Route::patch('/exams/{exam}/attempts/{attempt}/grade', [ExamAttemptReviewController::class, 'grade'])->name('exams.attempts.grade');
    });

    Route::get('/resources', [LearningResourceController::class, 'index'])->middleware(['role:admin', 'permission:resources.manage,resources.manage.assigned'])->name('resources.index');
    Route::post('/resources', [LearningResourceController::class, 'store'])->middleware(['role:admin', 'permission:resources.manage'])->name('resources.store');
    Route::patch('/resources/{resource}', [LearningResourceController::class, 'update'])->middleware(['role:admin', 'permission:resources.manage'])->name('resources.update');
    Route::delete('/resources/{resource}', [LearningResourceController::class, 'destroy'])->middleware(['role:admin', 'permission:resources.manage'])->name('resources.destroy');
    Route::patch('/resource-purchases/{purchase}/verify', [LearningResourceController::class, 'verifyPurchase'])->middleware('permission:resources.manage')->name('resources.purchases.verify');
    Route::get('/resources/{resource}/download', [LearningResourceController::class, 'download'])->middleware(['role:admin', 'permission:resources.view.all'])->name('resources.download');

    Route::get('/consultations', [ConsultationController::class, 'index'])->middleware('permission:consultations.manage')->name('consultations.index');
    Route::patch('/consultations/{consultation}', [ConsultationController::class, 'update'])->middleware('permission:consultations.manage')->name('consultations.update');
    Route::delete('/consultations/{consultation}', [ConsultationController::class, 'destroy'])->middleware('permission:consultations.manage')->name('consultations.destroy');

    Route::get('/messages', [MessageController::class, 'index'])->middleware('permission:messages.view.all,messages.view.assigned')->name('messages.index');
    Route::post('/messages/{message}/reply', [MessageController::class, 'reply'])->middleware('permission:messages.reply')->name('messages.reply');
    Route::post('/messages/{message}/close', [MessageController::class, 'close'])->middleware('permission:messages.view.all,messages.view.assigned')->name('messages.close');
    Route::post('/messages/{message}/reopen', [MessageController::class, 'reopen'])->middleware('permission:messages.view.all,messages.view.assigned')->name('messages.reopen');
    Route::post('/messages/{message}/notes', [MessageController::class, 'addNote'])->middleware('permission:messages.view.all,messages.view.assigned')->name('messages.notes.store');
    Route::post('/messages/{message}/participant', [MessageController::class, 'updateParticipant'])->middleware('permission:messages.view.all')->name('messages.participant.update');
    Route::patch('/messages/{message}/read', [MessageController::class, 'markRead'])->middleware('permission:messages.view.all,messages.view.assigned')->name('messages.read');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->middleware('permission:messages.view.all')->name('messages.destroy');

    Route::get('/announcements', [AnnouncementController::class, 'index'])->middleware('permission:announcements.manage,announcements.view')->name('announcements.index');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('permission:announcements.manage')->name('announcements.store');
    Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->middleware('permission:announcements.manage')->name('announcements.update');
    Route::patch('/announcements/{announcement}/status', [AnnouncementController::class, 'updateStatus'])->middleware('permission:announcements.manage')->name('announcements.status');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('permission:announcements.manage')->name('announcements.destroy');

    Route::get('/internal-notifications', [InternalNotificationController::class, 'index'])->middleware('permission:notifications.manage')->name('internal-notifications.index');
    Route::post('/internal-notifications', [InternalNotificationController::class, 'store'])->middleware(['permission:notifications.manage', 'throttle:10,1'])->name('internal-notifications.store');

    Route::get('/spotplayer', [SpotPlayerController::class, 'index'])->middleware(['role:admin', 'permission:integrations.manage,videos.manage.assigned'])->name('spotplayer.index');
    Route::patch('/spotplayer/settings', [SpotPlayerController::class, 'updateSettings'])->middleware('permission:integrations.manage')->name('spotplayer.settings');
    Route::post('/spotplayer/sync-courses', [SpotPlayerController::class, 'requestCourseSync'])->middleware('permission:integrations.manage')->name('spotplayer.sync-courses');
    Route::post('/spotplayer/videos', [SpotPlayerController::class, 'storeVideo'])->middleware(['role:admin', 'permission:integrations.manage'])->name('spotplayer.videos.store');
    Route::get('/spotplayer/videos/{video}/download', [SpotPlayerController::class, 'downloadStagedVideo'])->middleware(['role:admin', 'permission:integrations.manage'])->name('spotplayer.videos.download');
    Route::post('/spotplayer/videos/{video}/retry-processing', [SpotPlayerController::class, 'retryProcessing'])->middleware(['role:admin', 'permission:integrations.manage'])->name('spotplayer.videos.retry-processing');
    Route::post('/spotplayer/videos/{video}/approve', [SpotPlayerController::class, 'approveVideo'])->middleware('role:admin')->name('spotplayer.videos.approve');
    Route::post('/spotplayer/videos/{video}/reject', [SpotPlayerController::class, 'rejectVideo'])->middleware('role:admin')->name('spotplayer.videos.reject');
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
        Route::get('/meetings', [StudentParentMeetingController::class, 'index'])->name('meetings.index');
        Route::get('/homework', [StudentDashboardController::class, 'homework'])->middleware('permission:homework.submit')->name('homework.index');
        Route::get('/attendance', [StudentDashboardController::class, 'attendance'])->middleware('permission:attendance.view.own')->name('attendance');
        Route::get('/results', [StudentDashboardController::class, 'results'])->middleware('permission:exams.view.own')->name('results');
        Route::get('/resources', [StudentDashboardController::class, 'resources'])->middleware('permission:resources.view.enrolled')->name('resources.index');
        Route::get('/library', [StudentDashboardController::class, 'library'])->middleware('permission:dashboard.view')->name('library');
        Route::get('/live-classes', [StudentDashboardController::class, 'liveClasses'])->middleware('permission:dashboard.view')->name('live-classes');
        Route::get('/achievements', [StudentDashboardController::class, 'achievements'])->middleware('permission:dashboard.view')->name('achievements');
        Route::get('/profile', [StudentProfileController::class, 'show'])->middleware('permission:profile.update')->name('profile.edit');
        Route::put('/profile', [StudentProfileController::class, 'update'])->middleware('permission:profile.update')->name('profile.update');

        Route::middleware('permission:exams.view.own')->group(function (): void {
            Route::get('/exams', [StudentExamController::class, 'index'])->name('exams.index');
            Route::get('/exams/{exam}', [StudentExamController::class, 'show'])->name('exams.show');
            Route::get('/exams/{exam}/attempts/{attempt}', [StudentExamController::class, 'attempt'])->name('exams.attempt');
            Route::get('/exams/{exam}/result', [StudentExamController::class, 'result'])->name('exams.result');
        });
        Route::post('/exams/{exam}/start', [StudentExamController::class, 'start'])->middleware('permission:exams.attempt')->name('exams.start');
        Route::post('/exam-attempts/{attempt}/submit', [StudentExamController::class, 'submit'])->middleware('permission:exams.attempt')->name('exam-attempts.submit');
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
        // Independent teacher portal backed by the teacher-scoped dashboard service.
        Route::get('/', [\App\Http\Controllers\Teacher\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\Teacher\ProfileController::class, 'show'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\Teacher\ProfileController::class, 'update'])->name('profile.update');
        Route::get('/classes', [TeacherPortalController::class, 'classes'])->middleware('permission:classes.view.assigned')->name('classes');
        Route::get('/homework', [TeacherPortalController::class, 'homework'])->middleware('permission:homework.view.assigned')->name('homework');
        Route::post('/homework', [HomeworkController::class, 'store'])->middleware('permission:homework.manage.assigned')->name('homework.store');
        Route::patch('/homework-submissions/{submission}/review', [HomeworkController::class, 'review'])->middleware('permission:homework.grade')->name('homework.review');
        Route::get('/homework-submission-files/{file}/download', [HomeworkController::class, 'downloadSubmissionFile'])->middleware('permission:homework.view.assigned')->name('homework-submission-files.download');
        Route::get('/homework/{homework}/attachment', [HomeworkController::class, 'downloadAttachment'])->middleware('permission:homework.view.assigned')->name('homework.attachment');
        Route::get('/videos', [TeacherPortalController::class, 'videos'])->middleware('permission:videos.manage.assigned')->name('videos');
        Route::post('/videos', [SpotPlayerController::class, 'storeVideo'])->middleware('permission:videos.manage.assigned')->name('spotplayer.videos.store');
        Route::get('/videos/{video}/download', [SpotPlayerController::class, 'downloadStagedVideo'])->middleware('permission:videos.manage.assigned')->name('spotplayer.videos.download');
        Route::post('/videos/{video}/retry-processing', [SpotPlayerController::class, 'retryProcessing'])->middleware('permission:videos.manage.assigned')->name('spotplayer.videos.retry-processing');
        Route::post('/videos/{video}/resubmit', [SpotPlayerController::class, 'resubmitVideo'])->middleware('permission:videos.manage.assigned')->name('spotplayer.videos.resubmit');
        Route::post('/uploads/start', [ChunkUploadController::class, 'start'])->middleware(['permission:videos.manage.assigned', 'throttle:upload-start'])->name('uploads.start');
        Route::post('/uploads/resume', [ChunkUploadController::class, 'resume'])->middleware(['permission:videos.manage.assigned', 'throttle:upload-resume'])->name('uploads.resume');
        Route::post('/uploads/status', [ChunkUploadController::class, 'status'])->middleware(['permission:videos.manage.assigned', 'throttle:upload-status'])->name('uploads.status');
        Route::post('/uploads/chunk', [ChunkUploadController::class, 'chunk'])->middleware(['permission:videos.manage.assigned', 'throttle:upload-chunk'])->name('uploads.chunk');
        Route::post('/uploads/complete', [ChunkUploadController::class, 'complete'])->middleware(['permission:videos.manage.assigned', 'throttle:upload-complete'])->name('uploads.complete');
        Route::post('/uploads/cancel', [ChunkUploadController::class, 'cancel'])->middleware(['permission:videos.manage.assigned', 'throttle:upload-cancel'])->name('uploads.cancel');
        Route::get('/resources', [TeacherPortalController::class, 'resources'])->middleware('permission:resources.manage.assigned')->name('resources');
        Route::post('/resources', [LearningResourceController::class, 'store'])->middleware('permission:resources.manage.assigned')->name('resources.store');
        Route::get('/resources/{resource}/download', [LearningResourceController::class, 'download'])->middleware('permission:resources.manage.assigned')->name('resources.download');
        Route::get('/attendance', [TeacherPortalController::class, 'attendance'])->middleware('permission:attendance.view.assigned')->name('attendance');
        Route::post('/attendance', [TeacherPortalController::class, 'storeAttendance'])->middleware('permission:attendance.manage.assigned')->name('attendance.store');
        Route::get('/live', [TeacherPortalController::class, 'live'])->middleware('permission:classes.view.assigned')->name('live');
        Route::patch('/live/{session}', [TeacherPortalController::class, 'updateLiveSession'])->middleware('permission:classes.manage.assigned')->name('live.update');
        Route::get('/meetings', [TeacherPortalController::class, 'meetings'])->middleware('permission:classes.view.assigned')->name('meetings');
        Route::patch('/meetings/{meeting}', [TeacherPortalController::class, 'updateMeeting'])->middleware('permission:classes.manage.assigned')->name('meetings.update');
        Route::get('/messages', [TeacherPortalController::class, 'messages'])->middleware('permission:messages.view.assigned')->name('messages.index');
        Route::post('/messages/{message}/reply', [TeacherPortalController::class, 'reply'])->middleware('permission:messages.reply')->name('messages.reply');
        Route::middleware('permission:consultations.create')->group(function (): void {
            Route::get('/consultations', [TeacherConsultationController::class, 'index'])->name('consultations.index');
            Route::post('/consultations', [TeacherConsultationController::class, 'store'])->middleware('throttle:5,1')->name('consultations.store');
        });
        Route::middleware('permission:messages.view.assigned')->group(function (): void {
            Route::get('/message-recipients', [TeacherMessageController::class, 'recipients'])->middleware('throttle:30,1')->name('messages.recipients');
            Route::get('/messages/create', [TeacherMessageController::class, 'create'])->name('messages.create');
            Route::post('/messages', [TeacherMessageController::class, 'store'])->name('messages.store');
        });
    });

Route::middleware(['auth', 'role:parent', 'permission:dashboard.view'])
    ->prefix('parent')
    ->name('guardian.')
    ->group(function (): void {
        Route::get('/', [GuardianDashboardController::class, 'index'])->name('dashboard');
        Route::get('/attendance', [GuardianDashboardController::class, 'attendance'])->middleware('permission:attendance.view.children')->name('attendance');
        Route::get('/exams', [GuardianDashboardController::class, 'exams'])->middleware('permission:exams.view.children')->name('exams');
        Route::get('/homework', [GuardianDashboardController::class, 'homework'])->middleware('permission:homework.view.children')->name('homework');
        Route::get('/resources', [GuardianDashboardController::class, 'resources'])->middleware('permission:resources.view.enrolled')->name('resources.index');
        Route::get('/meetings', [GuardianParentMeetingController::class, 'index'])->name('meetings.index');
        Route::post('/meetings', [GuardianParentMeetingController::class, 'store'])->middleware('throttle:5,1')->name('meetings.store');
    });

Route::middleware(['auth', 'role:parent'])
    ->prefix('parent/consultations')
    ->name('guardian.consultations.')
    ->group(function (): void {
        Route::get('/', [GuardianConsultationController::class, 'index'])->middleware('permission:consultations.view.children')->name('index');
        Route::get('/create', [GuardianConsultationController::class, 'create'])->middleware('permission:consultations.create')->name('create');
        Route::post('/', [GuardianConsultationController::class, 'store'])->middleware('permission:consultations.create')->name('store');
        Route::get('/{consultation}', [GuardianConsultationController::class, 'show'])->middleware('permission:consultations.view.children')->name('show');
        Route::post('/{consultation}/replies', [GuardianConsultationController::class, 'reply'])->middleware('permission:consultations.create')->name('reply');
        Route::delete('/{consultation}', [GuardianConsultationController::class, 'destroy'])->middleware('permission:consultations.create')->name('destroy');
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

// Learning routes are maintained separately but share the canonical web controllers and policies.
require __DIR__.'/learning.php';
