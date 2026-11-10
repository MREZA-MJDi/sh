<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLearningResourceRequest;
use App\Http\Requests\Admin\UpdateLearningResourceRequest;
use App\Models\LearningResource;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\LearningResourceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LearningResourceController extends Controller
{
    public function index(ClassroomWorkspaceService $workspace): View
    {
        $user = auth()->user();
        $teacherView = $workspace->isTeacher($user);
        $classroomIds = $workspace->queryFor($user)->select('classrooms.id');
        $courseIds = $workspace->coursesQueryFor($user)->select('courses.id');

        $resources = LearningResource::query()
            ->with(['course', 'classroom', 'uploader'])
            ->when($teacherView, fn ($query) => $query->where(function ($scope) use ($classroomIds, $courseIds): void {
                $scope->whereIn('classroom_id', $classroomIds)
                    ->orWhereIn('course_id', $courseIds);
            }))
            ->latest()->paginate(15);

        $courses = $workspace->coursesQueryFor($user)->orderBy('title')->get(['id', 'title']);
        $classrooms = $workspace->queryFor($user)->orderBy('name')->get(['id', 'name']);

        return view('admin.resources.index', compact('resources', 'courses', 'classrooms', 'teacherView'));
    }

    public function store(
        StoreLearningResourceRequest $request,
        LearningResourceService $resources
    ): RedirectResponse {
        $resources->create($request->validated(), $request->user());

        return back()->with('success', 'منبع آموزشی در فضای خصوصی شیخان ذخیره شد.');
    }

    public function update(
        UpdateLearningResourceRequest $request,
        LearningResource $resource,
        LearningResourceService $resources,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $this->authorizeResourceAccess($resource, $workspace);
        $resources->update($resource, $request->validated());

        return back()->with('success', 'منبع آموزشی به‌روزرسانی شد.');
    }

    public function destroy(
        LearningResource $resource,
        LearningResourceService $resources,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $this->authorizeResourceAccess($resource, $workspace);
        $resources->delete($resource);

        return back()->with('success', 'منبع آموزشی حذف شد.');
    }

    public function download(LearningResource $resource, ClassroomWorkspaceService $workspace)
    {
        $this->authorizeResourceAccess($resource, $workspace);
        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path), 404);

        return Storage::disk('local')->download($resource->file_path);
    }

    private function authorizeResourceAccess(
        LearningResource $resource,
        ClassroomWorkspaceService $workspace
    ): void {
        $user = auth()->user();
        if (! $workspace->isTeacher($user)) {
            return;
        }

        $courseAllowed = $resource->course_id
            && $workspace->coursesQueryFor($user)->whereKey($resource->course_id)->exists();
        $classAllowed = $resource->classroom_id
            && $workspace->queryFor($user)->whereKey($resource->classroom_id)->exists();

        abort_unless($courseAllowed || $classAllowed, 403);
    }
}
