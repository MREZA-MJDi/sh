<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLearningResourceRequest;
use App\Http\Requests\Admin\UpdateLearningResourceRequest;
use App\Models\LearningResource;
use App\Models\LearningResourceAccessLog;
use App\Models\ResourcePurchase;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\LearningResourceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $resourcesQuery = LearningResource::query()->with(['course', 'classroom', 'uploader'])->withCount('accessLogs');

        if ($teacherView) {
            $resourcesQuery->where(function ($scope) use ($classroomIds, $courseIds): void {
                $scope->where(function ($classScoped) use ($classroomIds, $courseIds): void {
                    $classScoped->whereNotNull('classroom_id')
                        ->whereHas('classroom', function ($classrooms) use ($classroomIds): void {
                            $classrooms->whereIn('classrooms.id', $classroomIds)
                                ->where(function ($courseMatch): void {
                                    $courseMatch->whereNull('learning_resources.course_id')
                                        ->orWhereColumn('classrooms.course_id', 'learning_resources.course_id');
                                });
                        })
                        ->where(function ($courseScope) use ($courseIds): void {
                            $courseScope->whereNull('course_id')->orWhereIn('course_id', $courseIds);
                        });
                })->orWhere(function ($courseScoped) use ($courseIds): void {
                    $courseScoped->whereNull('classroom_id')->whereIn('course_id', $courseIds);
                });
            });
        }

        $resources = $resourcesQuery->latest()->paginate(15)->withQueryString();

        $purchasesQuery = ResourcePurchase::query()
            ->with(['resource:id,title', 'student:id,name'])
            ->where('status', 'pending_review')
            ->latest();

        if ($teacherView) {
            $purchasesQuery->whereHas('resource', function ($resource) use ($classroomIds, $courseIds): void {
                $resource->where(function ($scope) use ($classroomIds, $courseIds): void {
                    $scope->whereIn('classroom_id', clone $classroomIds)
                        ->orWhere(function ($courseOnly) use ($courseIds): void {
                            $courseOnly->whereNull('classroom_id')->whereIn('course_id', clone $courseIds);
                        });
                });
            });
        }

        $purchases = $purchasesQuery->paginate(15, ['*'], 'purchases')->withQueryString();

        $courses = $workspace->coursesQueryFor($user)->orderBy('title')->get(['id', 'title']);
        $classrooms = $workspace->queryFor($user)->orderBy('name')->get(['id', 'name']);

        return view('admin.resources.index', compact('resources', 'purchases', 'courses', 'classrooms', 'teacherView'));
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

    public function verifyPurchase(Request $request, ResourcePurchase $purchase): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($purchase->status === 'pending_review', 422, 'این درخواست خرید قبلاً تعیین تکلیف شده است.');

        $purchase->update([
            'status' => 'paid',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'paid_at' => now(),
        ]);

        $purchase->loadMissing('student');
        $purchase->student?->notify(new \App\Notifications\ResourcePurchaseNotification(
            $purchase,
            'پرداخت شما تأیید شد و دسترسی به محصول فعال است.'
        ));

        return back()->with('success', 'خرید تأیید شد؛ دسترسی محصول برای دانش‌آموز فعال گردید.');
    }

    public function download(LearningResource $resource, ClassroomWorkspaceService $workspace)
    {
        $this->authorizeResourceAccess($resource, $workspace);
        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path), 404);
        abort_unless($resource->downloadable || $resource->kind === 'pdf', 404);

        LearningResourceAccessLog::query()->create([
            'learning_resource_id' => $resource->id,
            'user_id' => auth()->id(),
            'student_id' => null,
            'event_type' => $resource->downloadable ? 'download' : 'view',
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 2000),
            'accessed_at' => now(),
        ]);

        if (! $resource->downloadable) {
            abort_unless($resource->kind === 'pdf', 404);
            return Storage::disk('local')->response($resource->file_path, basename($resource->file_path), ['X-Content-Type-Options' => 'nosniff'], 'inline');
        }

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

        if ($resource->classroom_id) {
            $classroom = $workspace->queryFor($user)->find($resource->classroom_id);
            abort_unless($classroom, 403);

            if ($resource->course_id) {
                abort_unless(
                    (int) $classroom->course_id === (int) $resource->course_id
                    && $workspace->coursesQueryFor($user)->whereKey($resource->course_id)->exists(),
                    403
                );
            }

            return;
        }

        abort_unless(
            $resource->course_id
            && $workspace->coursesQueryFor($user)->whereKey($resource->course_id)->exists(),
            403
        );
    }
}
