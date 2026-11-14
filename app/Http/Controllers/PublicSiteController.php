<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\SeoArticle;
use App\Models\SeoBanner;
use App\Models\SeoSetting;
use Illuminate\View\View;

final class PublicSiteController extends Controller
{
    public function home(): View
    {
        $settings = SeoSetting::current();

        $banners = SeoBanner::query()
            ->active()
            ->where('placement', 'home_hero')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $courses = Course::query()
            ->where('status', 'published')
            ->with([
                'teacher:id,name',
                'sections' => fn ($query) => $query
                    ->where('status', 'published')
                    ->withCount(['lessons' => fn ($lessons) => $lessons->where('status', 'published')]),
            ])
            ->latest()
            ->limit(6)
            ->get();

        $articles = SeoArticle::published()
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('public.home.index', compact('settings', 'banners', 'courses', 'articles'));
    }

    public function courses(): View
    {
        $settings = SeoSetting::current();

        $courses = Course::query()
            ->where('status', 'published')
            ->with([
                'teacher:id,name',
                'sections' => fn ($query) => $query
                    ->where('status', 'published')
                    ->withCount(['lessons' => fn ($lessons) => $lessons->where('status', 'published')]),
            ])
            ->latest()
            ->paginate(12);

        return view('public.courses.index', compact('settings', 'courses'));
    }

    public function course(string $slug): View
    {
        $settings = SeoSetting::current();

        $course = Course::query()
            ->where('status', 'published')
            ->where('slug', $slug)
            ->with([
                'teacher:id,name,subject',
                'sections' => fn ($query) => $query
                    ->where('status', 'published')
                    ->with(['lessons' => fn ($lessons) => $lessons
                        ->where('status', 'published')
                        ->orderBy('sort_order')
                        ->orderBy('id')]),
            ])
            ->firstOrFail();

        $landingCourse = [
            'title' => $course->title,
            'subtitle' => $course->description,
            'price' => $course->type === 'paid'
                ? number_format((int) $course->price).' تومان'
                : 'رایگان',
            'tracks' => $course->sections
                ->map(fn ($section) => [
                    'name' => $section->title,
                    'lessons' => $section->lessons->count(),
                ])
                ->all(),
        ];

        $curriculum = $course->sections
            ->map(fn ($section) => [
                'title' => $section->title,
                'lessons' => $section->lessons->pluck('title')->all(),
            ])
            ->all();

        $instructor = $course->teacher ? [
            'name' => $course->teacher->name,
            'role' => $course->teacher->subject ?: 'مدرس دوره',
            'bio' => null,
        ] : null;

        return view('public.courses.show', compact(
            'settings',
            'course',
            'landingCourse',
            'curriculum',
            'instructor',
        ));
    }
}
