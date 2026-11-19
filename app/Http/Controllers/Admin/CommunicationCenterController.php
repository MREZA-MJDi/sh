<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Models\\ContactMessage;
use App\\Models\\Consultation;
use App\\Models\\Message;
use App\\Models\\ParentMeeting;
use App\\Services\\Admin\\ClassroomWorkspaceService;
use App\\Services\\Admin\\MessageWorkspaceService;
use Illuminate\\Http\\Request;
use Illuminate\\Pagination\\LengthAwarePaginator;
use Illuminate\\View\\View;
use Illuminate\\Support\\Str;

final class CommunicationCenterController extends Controller
{
    private const PER_PAGE = 30;

    public function index(
        Request $request,
        ClassroomWorkspaceService $classrooms,
        MessageWorkspaceService $messageWorkspace,
    ): View {
        $filters = $request->validate([
            'type' => ['nullable', 'in:messages,contacts,consultations,meetings'],
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $term = trim((string) ($filters['q'] ?? ''));
        $selectedType = $filters['type'] ?? null;
        $page = max(1, (int) ($filters['page'] ?? 1));
        $take = $page * self::PER_PAGE;

        $counts = [
            'messages' => 0,
            'contacts' => 0,
            'consultations' => 0,
            'meetings' => 0,
        ];
        $items = collect();

        if ($user->hasAnyPermission(['messages.view.all', 'messages.view.assigned'])) {
            $query = $messageWorkspace->queryFor($user)
                ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                    $search->where('subject', 'like', "%{$term}%")
                        ->orWhere('body', 'like', "%{$term}%")
                        ->orWhereHas('sender', fn ($sender) => $sender->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('recipient', fn ($recipient) => $recipient->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('student', fn ($student) => $student->where('name', 'like', "%{$term}%"));
                }));

            $counts['messages'] = (clone $query)->count();

            if ($selectedType === null || $selectedType === 'messages') {
                $items = $items->concat(
                    (clone $query)
                        ->with(['sender:id,name', 'sender.roles:id,name', 'student:id,name'])
                        ->orderByDesc('created_at')->orderByDesc('id')->limit($take)->get()
                        ->map(fn (Message $message) => [
                            '_id' => $message->id,
                            'type' => 'messages',
                            'direction' => ($message->sender_id === $user->id || $message->sender?->hasRole(['admin', 'teacher']))
                                ? 'outgoing' : 'incoming',
                            'direction_label' => ($message->sender_id === $user->id || $message->sender?->hasRole(['admin', 'teacher']))
                                ? 'ارسالی' : 'دریافتی',
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
        }

        if ($user->hasPermissionTo('messages.view.all')) {
            $query = ContactMessage::query()
                ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                    $search->where('name', 'like', "%{$term}%")
                        ->orWhere('contact', 'like', "%{$term}%")
                        ->orWhere('topic', 'like', "%{$term}%")
                        ->orWhere('body', 'like', "%{$term}%");
                }));

            $counts['contacts'] = (clone $query)->count();

            if ($selectedType === null || $selectedType === 'contacts') {
                $items = $items->concat(
                    (clone $query)->orderByDesc('created_at')->orderByDesc('id')->limit($take)->get()
                        ->map(fn (ContactMessage $message) => [
                            '_id' => $message->id,
                            'type' => 'contacts',
                            'direction' => 'incoming',
                            'direction_label' => 'دریافتی',
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
        }

        if ($user->hasPermissionTo('consultations.manage')) {
            $query = Consultation::query()
                ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                    $search->where('topic', 'like', "%{$term}%")
                        ->orWhereHas('requester', fn ($requester) => $requester->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('student', fn ($student) => $student->where('name', 'like', "%{$term}%"))
                        ->orWhere('notes', 'like', "%{$term}%");
                }));

            $counts['consultations'] = (clone $query)->count();

            if ($selectedType === null || $selectedType === 'consultations') {
                $items = $items->concat(
                    (clone $query)->with(['requester:id,name', 'student:id,name', 'teacher:id,name'])
                        ->orderByDesc('created_at')->orderByDesc('id')->limit($take)->get()
                        ->map(fn (Consultation $consultation) => [
                            '_id' => $consultation->id,
                            'type' => 'consultations',
                            'direction' => 'incoming',
                            'direction_label' => 'دریافتی',
                            'label' => 'درخواست مشاوره',
                            'title' => $consultation->topic ?: 'درخواست مشاوره',
                            'sender' => $consultation->requester?->name ?? 'کاربر',
                            'context' => $consultation->student?->name ?: $consultation->teacher?->name,
                            'summary' => 'نوع درخواست: '.$consultation->type.' · '.($consultation->notes ?: 'بدون یادداشت'),
                            'status' => $consultation->status,
                            'created_at' => $consultation->created_at,
                            'url' => route('admin.consultations.index', ['consultation' => $consultation->id]).'#consultation-'.$consultation->id,
                        ])
                );
            }
        }

        if ($user->hasAnyPermission(['classes.manage', 'classes.manage.assigned'])) {
            $query = ParentMeeting::query()
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

            $counts['meetings'] = (clone $query)->count();

            if ($selectedType === null || $selectedType === 'meetings') {
                $items = $items->concat(
                    (clone $query)->with(['student:id,name', 'guardian:id,name', 'classroom:id,name'])
                        ->orderByDesc('created_at')->orderByDesc('id')->limit($take)->get()
                        ->map(fn (ParentMeeting $meeting) => [
                            '_id' => $meeting->id,
                            'type' => 'meetings',
                            'direction' => 'incoming',
                            'direction_label' => 'دریافتی',
                            'label' => 'درخواست جلسه اولیا',
                            'title' => $meeting->title,
                            'sender' => $meeting->guardian?->name ?? 'ولی',
                            'context' => trim(($meeting->student?->name ?? '').' · '.($meeting->classroom?->name ?? '')),
                            'summary' => Str::limit(strip_tags($meeting->message ?: 'بدون توضیح'), 180),
                            'status' => $meeting->status,
                            'created_at' => $meeting->created_at,
                            'url' => route('admin.parent-meetings.index', ['meeting' => $meeting->id]).'#parent-meeting-'.$meeting->id,
                        ])
                );
            }
        }

        $total = array_sum($counts);
        $items = $items->sort(function (array $left, array $right): int {
            $dateOrder = ($right['created_at']?->getTimestamp() ?? 0)
                <=> ($left['created_at']?->getTimestamp() ?? 0);

            return $dateOrder !== 0 ? $dateOrder : ($right['_id'] <=> $left['_id']);
        })->values();

        $pageItems = $items
            ->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)
            ->map(function (array $item): array {
                unset($item['_id']);

                return $item;
            })->values();

        $items = new LengthAwarePaginator(
            $pageItems,
            $total,
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.communication-center.index', compact('items', 'counts', 'filters'));
    }
}
