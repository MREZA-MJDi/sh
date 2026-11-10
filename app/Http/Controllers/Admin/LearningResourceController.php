<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLearningResourceRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\LearningResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LearningResourceController extends Controller
{
    public function index(): View
    {
        $resources = LearningResource::with(['course', 'classroom', 'uploader'])->latest()->paginate(15);
        $courses = Course::orderBy('title')->get(['id', 'title']);
        $classrooms = Classroom::orderBy('name')->get(['id', 'name']);

        return view('admin.resources.index', compact('resources', 'courses', 'classrooms'));
    }

    public function store(StoreLearningResourceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $path = isset($data['file']) ? $data['file']->store('private/resources', 'local') : null;
        $isFree = (bool) ($data['is_free'] ?? false);

        LearningResource::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'kind' => $data['kind'],
            'course_id' => $data['course_id'] ?? null,
            'classroom_id' => $data['classroom_id'] ?? null,
            'is_free' => $isFree,
            'price' => $isFree ? 0 : (int) ($data['price'] ?? 0),
            'visibility' => $data['visibility'],
            'file_path' => $path,
            'status' => $data['status'],
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'منبع آموزشی ثبت شد.');
    }

    public function destroy(LearningResource $resource): RedirectResponse
    {
        if ($resource->file_path) {
            Storage::disk('local')->delete($resource->file_path);
        }

        $resource->delete();

        return back()->with('success', 'منبع آموزشی حذف شد.');
    }

    public function download(LearningResource $resource)
    {
        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path), 404);

        return Storage::disk('local')->download($resource->file_path);
    }
}
