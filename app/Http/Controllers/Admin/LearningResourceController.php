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

        if (isset($data['file']) && ! $path) {
            return back()->withErrors(['file' => 'ذخیره فایل در فضای خصوصی انجام نشد.'])->withInput();
        }

        try {
            DB::transaction(function () use ($data, $path): void {
                $isFree = (bool) ($data['is_free'] ?? false);

                LearningResource::create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'kind' => $data['kind'],
                    'course_id' => $data['course_id'] ?? null,
                    'classroom_id' => $data['classroom_id'] ?? null,
                    'is_free' => $isFree,
                    'price' => $isFree ? 0 : (int) $data['price'],
                    'visibility' => $data['visibility'],
                    'file_path' => $path,
                    'status' => $data['status'],
                    'uploaded_by' => auth()->id(),
                ]);
            });
        } catch (\\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        return back()->with('success', 'منبع آموزشی ثبت شد.');
    }

    public function update(
        UpdateLearningResourceRequest $request,
        LearningResource $resource
    ): RedirectResponse {
        $data = $request->validated();
        $newPath = isset($data['file']) ? $data['file']->store('private/resources', 'local') : null;

        if (isset($data['file']) && ! $newPath) {
            return back()->withErrors(['file' => 'ذخیره فایل جدید در فضای خصوصی انجام نشد.'])->withInput();
        }

        $previousPath = $resource->file_path;

        try {
            DB::transaction(function () use ($resource, $data, $newPath): void {
                $isFree = (bool) ($data['is_free'] ?? false);

                $resource->update([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'kind' => $data['kind'],
                    'course_id' => $data['course_id'] ?? null,
                    'classroom_id' => $data['classroom_id'] ?? null,
                    'is_free' => $isFree,
                    'price' => $isFree ? 0 : (int) $data['price'],
                    'visibility' => $data['visibility'],
                    'file_path' => $newPath ?: $resource->file_path,
                    'status' => $data['status'],
                ]);
            });
        } catch (\\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath && $previousPath) {
            Storage::disk('local')->delete($previousPath);
        }

        return back()->with('success', 'منبع آموزشی به‌روزرسانی شد.');
    }

    public function destroy(LearningResource $resource): RedirectResponse
    {
        $path = $resource->file_path;
        $resource->delete();

        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return back()->with('success', 'منبع آموزشی حذف شد.');
    }

    public function download(LearningResource $resource)
    {
        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path), 404);

        return Storage::disk('local')->download($resource->file_path);
    }
}
