<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Consultation;
use App\Models\Message;
use App\Models\ParentMeeting;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\MessageWorkspaceService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class CommunicationCenterController extends Controller
{
    public function index(
        Request $request,
        ClassroomWorkspaceService $classrooms,
        MessageWorkspaceService $messageWorkspace,
    ): View {
        $filters = $request->validate([
            'type' => ['nullable', 'in:messages,contacts,consultations,meetings'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);
        $user = $request->user();
        $term = trim((string) ($filters['q'] ?? ''));
        $items = collect();

        if ($user->hasAnyPermission(['messages.view.all', 'messages.view.assigned'])) {
            $items = $items->concat(
                $messageWorkspace->queryFor($user)
                    ->with(['sender:id,name', 'student:id,name'])
                    ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                        $search->where('subject', 'like', "%{$term}%")
                            ->orWhere('body', 'like', "%{$term}%");
                    }))
                    ->latest()->limit(50)->get()
                    ->map(fn (Message $message) => [
                        'type' => 'messages',
                        'label' => 'پیام گفت‌وگو',
                        'title' => $message->subject ?: 'گفت‌وگوی بدون موضوع',
                        'sender' => $message->sender?->name ?? 'کاربر',
                        'context' => $message->student?->name,
                        'summary' => Str::limit(strip_tags($message->body), 180),
                        'status' => $message->status,
                        'created_at' => $message->created_at,
                        'url' => route('admin.messages.index', ['message' => $message->id]),
                    ])
            );
        }

        if ($user->hasPermissionTo('messages.view.all')) {
            $items = $items->concat(
                ContactMessage::query()
                    ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                        $search->where('name', 'like', "%{$term}%")
                            ->orWhere('contact', 'like', "%{$term}%")
                            ->orWhere('topic', 'like', "%{$term}%")
                            ->orWhere('body', 'like', "%{$term}%");
                    }))
                    ->latest()->limit(50)->get()
                    ->map(fn (ContactMessage $message) => [
                        'type' => 'contacts',
                        'label' => 'پیام فرم تماس',
                        'title' => $message->topic ?: 'پیام عمومی',
                        'sender' => $message->name,
                        'context' => $message->contact,
                        'summary' => Str::limit(strip_tags($message->body), 180),
                        'status' => $message->status,
                        'created_at' => $message->created_at,
                        'url' => route('admin.contact-messages.index', ['message' => $message->id]).'#contact-message-'.$message->id,
                    ])
            );
        }

        if ($user->hasPermissionTo('consultations.manage')) {
            $items = $items->concat(
                Consultation::query()
                    ->with(['requester:id,name', 'student:id,name', 'teacher:id,name'])
                    ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                        $search->where('topic', 'like', "%{$term}%")
                            ->orWhereHas('requester', fn ($requester) => $requester->where('name', 'like', "%{$term}%"))
                            ->orWhereHas('student', fn ($student) => $student->where('name', 'like', "%{$term}%"));
                    }))
                    ->latest()->limit(50)->get()
                    ->map(fn (Consultation $consultation) => [
                        'type' => 'consultations',
                        'label' => 'درخواست مشاوره',
                        'title' => $consultation->topic ?: 'درخواست مشاوره',
                        'sender' => $consultation->requester?->name ?? 'کاربر',
                        'context' => $consultation->student?->name ?: $consultation->teacher?->name,
                        'summary' => 'نوع درخواست: '.$consultation->type.' · '.($consultation->notes ?: 'بدون یادداشت'),
                        'status' => $consultation->status,
                        'created_at' => $consultation->created_at,
                        'url' => route('admin.consultations.index').'#consultation-'.$consultation->id,
                    ])
            );
        }

        if ($user->hasAnyPermission(['classes.manage', 'classes.manage.assigned'])) {
            $meetingQuery = ParentMeeting::query()
                ->with(['student:id,name', 'guardian:id,name', 'classroom:id,name'])
                ->when($classrooms->isTeacher($user), fn ($query) => $query->whereIn(
                    'classroom_id',
                    $classrooms->queryFor($user)->select('classrooms.id')
                ))
                ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                    $search->where('title', 'like', "%{$term}%")
                        ->orWhere('message', 'like', "%{$term}%")
                        ->orWhereHas('student', fn ($student) => $student->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('guardian', fn ($guardian) => $guardian->where('name', 'like', "%{$term}%"));
                }));

            $items = $items->concat(
                $meetingQuery->latest()->limit(50)->get()
                    ->map(fn (ParentMeeting $meeting) => [
                        'type' => 'meetings',
                        'label' => 'درخواست جلسه اولیا',
                        'title' => $meeting->title,
                        'sender' => $meeting->guardian?->name ?? 'ولی',
                        'context' => trim(($meeting->student?->name ?? '').' · '.($meeting->classroom?->name ?? '')),
                        'summary' => Str::limit(strip_tags($meeting->message ?: 'بدون توضیح'), 180),
                        'status' => $meeting->status,
                        'created_at' => $meeting->created_at,
                        'url' => route('admin.parent-meetings.index').'#parent-meeting-'.$meeting->id,
                    ])
            );
        }

        $counts = $items->groupBy('type')->map->count();
        if (filled($filters['type'] ?? null)) {
            $items = $items->where('type', $filters['type']);
        }
        $items = $items->sortByDesc(fn (array $item) => $item['created_at']?->getTimestamp() ?? 0)->values();

        return view('admin.communication-center.index', compact('items', 'counts', 'filters'));
    }
}
