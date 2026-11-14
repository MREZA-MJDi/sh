<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\SeoArticle;
use App\Models\SeoBanner;
use App\Models\SeoSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PublicSiteController extends Controller
{
    public function home(): View
    {
        $settings = SeoSetting::current();
        $banners = SeoBanner::query()->active()->where('placement', 'home_hero')
            ->orderBy('sort_order')->orderBy('id')->get();

        $courseQuery = Course::query()->where('status', 'published')
            ->with(['teacher:id,name', 'sections' => fn ($query) => $query
                ->where('status', 'published')
                ->withCount(['lessons' => fn ($lessons) => $lessons->where('status', 'published')])])
            ->withCount('enrollments');

        $courses = (clone $courseQuery)->latest()->limit(6)->get();
        $popularCourses = (clone $courseQuery)->orderByDesc('enrollments_count')->latest()->limit(6)->get();

        $teachers = User::query()->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'teacher'))
            ->withCount(['assignedCourses as published_courses_count' => fn ($courses) => $courses->where('status', 'published')])
            ->orderBy('name')->limit(8)->get(['id', 'name', 'subject']);

        $articles = SeoArticle::published()->latest('published_at')->limit(3)->get();
        $categories = Course::query()->where('status', 'published')->whereNotNull('subject')
            ->where('subject', '<>', '')->distinct()->orderBy('subject')->pluck('subject');

        $stats = [
            'courses' => Course::query()->where('status', 'published')->count(),
            'teachers' => User::query()->where('is_active', true)->whereHas('roles', fn ($roles) => $roles->where('name', 'teacher'))->count(),
            'lessons' => Lesson::query()->where('status', 'published')->whereHas('section', fn ($sections) => $sections
                ->where('status', 'published')->whereHas('course', fn ($courses) => $courses->where('status', 'published')))->count(),
            'enrollments' => CourseEnrollment::query()->whereHas('course', fn ($courses) => $courses->where('status', 'published'))->count(),
        ];

        return view('public.home.index', compact('settings', 'banners', 'courses', 'popularCourses', 'teachers', 'articles', 'categories', 'stats'));
    }

    public function courses(Request $request): View
    {
        $settings = SeoSetting::current();
        $query = Course::query()->where('status', 'published')
            ->with(['teacher:id,name', 'sections' => fn ($sections) => $sections
                ->where('status', 'published')
                ->withCount(['lessons' => fn ($lessons) => $lessons->where('status', 'published')])])
            ->withCount('enrollments');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($builder) => $builder->where('title', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')
                ->orWhere('subject', 'like', '%'.$search.'%'));
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

    public function course(string $slug): View
    {
        $settings = SeoSetting::current();
        $course = Course::query()->where('status', 'published')->where('slug', $slug)
            ->with(['teacher:id,name,subject', 'sections' => fn ($sections) => $sections
                ->where('status', 'published')
                ->with(['lessons' => fn ($lessons) => $lessons->where('status', 'published')->orderBy('sort_order')->orderBy('id')])])
            ->withCount('enrollments')->firstOrFail();

        $relatedCourses = Course::query()->where('status', 'published')->where('id', '<>', $course->id)
            ->when($course->subject, fn ($query) => $query->where('subject', $course->subject))
            ->with('teacher:id,name')->withCount('enrollments')->latest()->limit(3)->get();

        return view('public.courses.show', compact('settings', 'course', 'relatedCourses'));
    }

    public function teachers(): View
    {
        $settings = SeoSetting::current();
        $teachers = User::query()->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'teacher'))
            ->withCount(['assignedCourses as published_courses_count' => fn ($courses) => $courses->where('status', 'published')])
            ->with(['assignedCourses' => fn ($courses) => $courses->where('status', 'published')->select('id', 'teacher_id', 'title', 'slug')])
            ->orderBy('name')->paginate(12);

        return view('public.teachers.index', compact('settings', 'teachers'));
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
        $courses = Course::query()->where('status', 'published')->whereIn('id', $ids)
            ->with('teacher:id,name')->get();
        $request->session()->put('public_cart', $courses->modelKeys());

        return view('public.cart', ['settings' => SeoSetting::current(), 'courses' => $courses]);
    }

    public function addToCart(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->status === 'published', 404);
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
