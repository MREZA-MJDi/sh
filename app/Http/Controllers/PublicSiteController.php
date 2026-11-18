<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\HomeQuizQuestion;
use App\Models\CourseEnrollment;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\SeoArticle;
use App\Models\SeoBanner;
use App\Models\SeoSetting;
use App\Models\SeoTestimonial;
use App\Models\SpotPlayerLicense;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use App\Services\Learning\LearningAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PublicSiteController extends Controller
{
    public function home(): View
    {
        $settings = SeoSetting::current();
        $homeContent = is_array($settings->home_sections) ? $settings->home_sections : SeoSetting::defaultHomeSections();
        $homeBanners = SeoBanner::query()->active()->where('placement', 'home_hero')
            ->orderBy('sort_order')->orderBy('id')->limit(3)->get();
        $homeQuizTotal = HomeQuizQuestion::published()->count();

        $courseQuery = Course::query()->where('status', 'published')
            ->with(['teacher' => fn ($teacher) => $teacher->where('is_active', true), 'sections' => fn ($sections) => $sections
                ->where('status', 'published')
                ->withCount(['lessons' => fn ($lessons) => $lessons->where('status', 'published')])])
            ->withCount(['enrollments' => fn ($enrollments) => $enrollments->currentlyActive()]);

        $courses = (clone $courseQuery)->latest()->limit(6)->get();
        $popularCourses = (clone $courseQuery)->orderByDesc('enrollments_count')->latest()->limit(6)->get();

        $teachers = User::query()->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'teacher'))
            ->withCount(['assignedCourses as published_courses_count' => fn ($courses) => $courses->where('status', 'published')])
            ->orderBy('name')->limit(8)->get(['id', 'name', 'subject']);

        $articles = SeoArticle::published()->with('author')->latest('published_at')->limit(3)->get();
        $testimonials = SeoTestimonial::query()->where('status', 'published')->orderBy('sort_order')->orderByDesc('id')->limit(6)->get();
        $categories = Course::query()->where('status', 'published')->whereNotNull('subject')
            ->where('subject', '<>', '')->distinct()->orderBy('subject')->pluck('subject');

        $stats = [
            'courses' => Course::query()->where('status', 'published')->count(),
            'teachers' => User::query()->where('is_active', true)->whereHas('roles', fn ($roles) => $roles->where('name', 'teacher'))->count(),
            'lessons' => Lesson::query()->where('status', 'published')->whereHas('section', fn ($sections) => $sections
                ->where('status', 'published')->whereHas('course', fn ($courses) => $courses->where('status', 'published')))->count(),
            'enrollments' => CourseEnrollment::query()->currentlyActive()->whereHas('course', fn ($courses) => $courses->where('status', 'published'))->count(),
        ];

        return view('public.home.index', compact('settings', 'homeContent', 'homeBanners', 'homeQuizTotal', 'courses', 'popularCourses', 'teachers', 'articles', 'categories', 'stats', 'testimonials'));
    }

    public function courses(Request $request): View
    {
        $settings = SeoSetting::current();
        $query = Course::query()->where('status', 'published')
            ->with(['teacher' => fn ($teacher) => $teacher->where('is_active', true)])
            ->withCount(['enrollments' => fn ($enrollments) => $enrollments->currentlyActive()]);

        if ($search = trim((string) $request->query('q'))) {
            $term = '%'.$search.'%';

            $query->where(function ($builder) use ($term): void {
                $builder->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('subject', 'like', $term)
                    ->orWhereHas('teacher', fn ($teacher) => $teacher
                        ->where('is_active', true)
                        ->whereHas('roles', fn ($roles) => $roles->where('name', 'teacher'))
                        ->where('name', 'like', $term))
                    ->orWhereHas('sections', fn ($sections) => $sections
                        ->where('status', 'published')
                        ->whereHas('lessons', fn ($lessons) => $lessons
                            ->where('status', 'published')
                            ->where('title', 'like', $term)));
            });
        }
        if ($subject = $request->query('subject')) $query->where('subject', $subject);
        if ($grade = $request->query('grade')) $query->where('grade', $grade);
        if ($request->query('price') === 'free') $query->where('type', 'free');
        if ($request->query('price') === 'paid') $query->where('type', 'paid');

        match ($request->query('sort')) {
            'popular' => $query->orderByDesc('enrollments_count')->latest(),
            'price_asc' => $query->orderByRaw('CASE WHEN type = ? THEN 0 ELSE 1 END', ['free'])->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->latest(),
        };

        $courses = $query->paginate(12)->withQueryString();
        $subjects = Course::query()->where('status', 'published')->whereNotNull('subject')
            ->where('subject', '<>', '')->distinct()->orderBy('subject')->pluck('subject');
        $grades = Course::query()->where('status', 'published')->whereNotNull('grade')
            ->distinct()->orderBy('grade')->pluck('grade');

        return view('public.courses.index', compact('settings', 'courses', 'subjects', 'grades'));
    }

    public function course(
        Request $request,
        string $slug,
        CourseEnrollmentService $enrollments,
        LearningAccessService $learningAccess
    ): View
    {
        $settings = SeoSetting::current();
        $isAdminPreview = $request->boolean('preview')
            && $request->user()?->hasRole('admin')
            && $request->user()?->hasAnyPermission(['courses.manage']);
        $course = Course::query()
            ->when(! $isAdminPreview, fn ($query) => $query->where('status', 'published'))
            ->where('slug', $slug)
            ->with(['teacher' => fn ($teacher) => $teacher->where('is_active', true)->select('id', 'name', 'subject', 'profile_note')])
            ->withCount([
                'enrollments' => fn ($query) => $query->currentlyActive(),
                'classrooms as active_classrooms_count' => fn ($classes) => $classes->where('status', 'active'),
            ])
            ->firstOrFail();

        $upcomingOnlineSessionCount = ClassSession::query()
            ->where('session_type', 'online')
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', now())
            ->whereHas('classroom', fn ($classrooms) => $classrooms
                ->where('course_id', $course->id)
                ->where('status', 'active'))
            ->count();

        $upcomingSessions = ClassSession::query()
            ->where('session_type', 'online')
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', now())
            ->whereHas('classroom', fn ($classrooms) => $classrooms
                ->where('course_id', $course->id)
                ->where('status', 'active'))
            ->with('classroom:id,name,meeting_url,skyroom_room_id,skyroom_status')
            ->orderBy('starts_at')
            ->limit(8)
            ->get();

        $features = [];
        foreach (['recorded_video', 'live_class', 'resources', 'assignments', 'exams', 'projects'] as $feature) {
            $features[$feature] = $course->featureEnabled($feature);
        }

        $activeClassroomIds = $request->user()?->hasRole('student')
            ? $learningAccess->activeClassroomIds($request->user())->pluck('classrooms.id')->map(fn ($id) => (int) $id)->all()
            : [];

        $upcomingSessions->each(function (ClassSession $session) use ($activeClassroomIds): void {
            $session->setAttribute('can_join', in_array((int) $session->classroom_id, $activeClassroomIds, true));
        });

        // The public curriculum is a preview, not an unbounded dump of every
        // lesson. Keep sections and per-section lesson previews paginated/bounded.
        $sections = $course->sections()
            ->where('status', 'published')
            ->withCount(['lessons as published_lessons_count' => fn ($lessons) => $lessons->where('status', 'published')])
            ->with(['lessons' => fn ($lessons) => $lessons
                ->where('status', 'published')
                ->orderBy('sort_order')->orderBy('id')->limit(20)])
            ->orderBy('sort_order')->orderBy('id')
            ->paginate(8, ['*'], 'sections_page')
            ->withQueryString();

        $totalLessons = Lesson::query()
            ->where('status', 'published')
            ->whereHas('section', fn ($section) => $section
                ->where('course_id', $course->id)
                ->where('status', 'published'))
            ->count();

        $hasCourseAccess = $request->user()?->hasRole('student') && $request->user()->is_active
            ? $enrollments->hasActiveAccess($request->user(), $course)
            : false;

        // Video playback is exposed only when both the enrollment and the
        // provider-issued license are currently valid. Embed hosts are explicitly
        // allow-listed in services.spotplayer.embed_hosts (never trust arbitrary URLs).
        $videoLicense = $hasCourseAccess && ($features['recorded_video'] ?? false)
            ? SpotPlayerLicense::query()
                ->where('student_id', $request->user()->id)
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->whereNull('revoked_at')
                ->whereNotNull('external_license_id')
                ->whereNotNull('license_key')
                ->latest('id')
                ->first()
            : null;

        $allowedEmbedHosts = array_map('strtolower', config('services.spotplayer.embed_hosts', []));
        $playableVideos = $hasCourseAccess && ($features['recorded_video'] ?? false)
            ? SpotPlayerVideo::query()
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->where(function ($query): void {
                    $query->where(function ($local): void {
                        $local->whereNotNull('file_path')->whereNull('external_id');
                    })->orWhere(function ($external): void {
                        $external->whereNotNull('embed_url')->whereNotNull('external_id');
                    });
                })
                ->orderBy('id')
                ->get(['id', 'title', 'embed_url', 'file_path', 'external_id'])
                ->filter(function (SpotPlayerVideo $video) use ($allowedEmbedHosts, $videoLicense): bool {
                    if (filled($video->file_path) && blank($video->external_id)) {
                        return true;
                    }
                    if (! $videoLicense || blank($video->embed_url)) {
                        return false;
                    }
                    $parts = parse_url((string) $video->embed_url);
                    $host = strtolower((string) ($parts['host'] ?? ''));
                    return ($parts['scheme'] ?? null) === 'https'
                        && $host !== ''
                        && in_array($host, $allowedEmbedHosts, true);
                })
                ->values()
            : collect();

        $canWatchCourseVideos = $hasCourseAccess && $playableVideos->isNotEmpty();

        $relatedCourses = Course::query()->where('status', 'published')->where('id', '<>', $course->id)
            ->when($course->subject, fn ($query) => $query->where('subject', $course->subject))
            ->with([
                'teacher' => fn ($teacher) => $teacher->where('is_active', true)->select('id', 'name'),
                'sections' => fn ($sections) => $sections->where('status', 'published')
                    ->withCount(['lessons' => fn ($lessons) => $lessons->where('status', 'published')]),
            ])->withCount(['enrollments' => fn ($query) => $query->currentlyActive()])->latest()->limit(3)->get();

        return view('public.courses.show', compact(
            'settings',
            'course',
            'sections',
            'totalLessons',
            'relatedCourses',
            'hasCourseAccess',
            'upcomingOnlineSessionCount',
            'upcomingSessions',
            'features',
            'canWatchCourseVideos',
            'playableVideos',
            'isAdminPreview'
        ));
    }

    public function playCourseVideo(
        Request $request,
        string $slug,
        int $video,
        CourseEnrollmentService $enrollments
    ): RedirectResponse {
        $student = $request->user();

        abort_unless($student && $student->is_active && $student->hasRole('student'), 403);

        $course = Course::query()
            ->where('status', 'published')
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($enrollments->hasActiveAccess($student, $course), 403);
        abort_unless($course->featureEnabled('recorded_video'), 404);

        $courseVideo = SpotPlayerVideo::query()
            ->where('id', $video)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->firstOrFail();

        if (filled($courseVideo->file_path) && blank($courseVideo->external_id)) {
            return redirect()->route('student.videos.show', $courseVideo);
        }

        $license = SpotPlayerLicense::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->whereNotNull('external_license_id')
            ->whereNotNull('license_key')
            ->first();

        abort_unless($license && filled($courseVideo->embed_url) && filled($courseVideo->external_id), 403);

        $parts = parse_url((string) $courseVideo->embed_url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $allowedHosts = array_map('strtolower', config('services.spotplayer.embed_hosts', []));

        abort_unless(
            ($parts['scheme'] ?? null) === 'https'
                && $host !== ''
                && in_array($host, $allowedHosts, true),
            403
        );

        return redirect()->away($courseVideo->embed_url)
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function teachers(Request $request): View
    {
        $settings = SeoSetting::current();
        $query = User::query()->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'teacher'));

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($builder) => $builder->where('name', 'like', '%'.$search.'%')
                ->orWhere('subject', 'like', '%'.$search.'%'));
        }
        if ($subject = $request->query('subject')) {
            $query->where('subject', $subject);
        }

        $teachers = $query
            ->withCount(['assignedCourses as published_courses_count' => fn ($courses) => $courses->where('status', 'published')])
            ->orderBy('name')->paginate(12)->withQueryString();

        $subjects = User::query()->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'teacher'))
            ->whereNotNull('subject')->where('subject', '<>', '')
            ->distinct()->orderBy('subject')->pluck('subject');

        return view('public.teachers.index', compact('settings', 'teachers', 'subjects'));
    }

    public function teacher(User $teacher): View
    {
        abort_unless($teacher->is_active && $teacher->hasRole('teacher'), 404);

        $settings = SeoSetting::current();

        $courses = Course::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', 'published')
            ->with([
                'teacher' => fn ($query) => $query->where('is_active', true)->select('id', 'name', 'subject'),
                'sections' => fn ($sections) => $sections
                    ->where('status', 'published')
                    ->withCount(['lessons' => fn ($lessons) => $lessons->where('status', 'published')]),
            ])
            ->withCount(['enrollments' => fn ($enrollments) => $enrollments->currentlyActive()])
            ->latest()->limit(12)
            ->get();

        $stats = [
            'courses_count' => $courses->count(),
            'lessons_count' => $courses->sum(fn ($course) => (int) $course->sections->sum('lessons_count')),
            'enrollments_count' => (int) $courses->sum('enrollments_count'),
        ];

        return view('public.teachers.show', compact('settings', 'teacher', 'courses', 'stats'));
    }

    public function contact(): View
    {
        return view('public.contact', ['settings' => SeoSetting::current()]);
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'contact' => ['required', 'string', 'max:190'],
            'topic' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::query()->create($data);

        return back()->with('status', 'پیام شما ثبت شد. تیم شیخان پس از بررسی با شما تماس می‌گیرد.');
    }

    public function cart(Request $request): View
    {
        $ids = collect($request->session()->get('public_cart', []))->filter(fn ($id) => is_numeric($id))->unique()->values();
        $courses = Course::query()->where('status', 'published')->where('type', 'paid')->whereIn('id', $ids)
            ->with(['teacher' => fn ($teacher) => $teacher->where('is_active', true)->select('id', 'name')])
            ->orderBy('title')->limit(50)->get();
        $request->session()->put('public_cart', $courses->modelKeys());

        return view('public.cart', ['settings' => SeoSetting::current(), 'courses' => $courses]);
    }

    public function addToCart(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->status === 'published' && $course->type === 'paid', 404);
        $ids = collect($request->session()->get('public_cart', []))->push($course->id)->unique()->values()->all();
        $request->session()->put('public_cart', $ids);

        return redirect()->route('public.cart')->with('status', 'دوره به سبد خرید اضافه شد.');
    }

    public function removeFromCart(Request $request, Course $course): RedirectResponse
    {
        $ids = collect($request->session()->get('public_cart', []))->reject(fn ($id) => (int) $id === (int) $course->id)->values()->all();
        $request->session()->put('public_cart', $ids);

        return redirect()->route('public.cart')->with('status', 'دوره از سبد خرید حذف شد.');
    }
}
