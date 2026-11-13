<?php

namespace App\\Http\\Controllers;

use App\\Models\\Course;
use App\\Models\\SeoArticle;
use App\\Models\\SeoSetting;
use Illuminate\\View\\View;

final class PublicHomeController extends Controller
{
    public function __invoke(): View
    {
        $settings = SeoSetting::current();

        $courses = Course::query()
            ->where('status', 'published')
            ->with('teacher:id,name')
            ->withCount('classrooms')
            ->latest('updated_at')
            ->limit(6)
            ->get(['id', 'title', 'slug', 'description', 'subject', 'grade', 'type', 'price', 'teacher_id']);

        $articles = SeoArticle::published()
            ->latest('published_at')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'featured_image_path', 'image_alt', 'published_at']);

        return view('public.home', compact('settings', 'courses', 'articles'));
    }
}
