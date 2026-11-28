<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class LookupController extends Controller
{
    private const RESULT_LIMIT = 20;

    public function students(Request $request): JsonResponse
    {
        $user = $request->user();
        $canSeeContact = $user?->hasPermissionTo('users.manage') ?? false;
        abort_unless($canSeeContact || ($user?->hasRole('admin') && $user->hasPermissionTo('courses.manage')), 403);

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $term = trim($validated['q']);
        if (mb_strlen($term) < 2) {
            throw ValidationException::withMessages([
                'q' => 'برای جستجو حداقل دو حرف معتبر وارد کنید.',
            ]);
        }

        $pattern = '%' . addcslashes($term, '%_\\') . '%';

        $matches = User::query()
            ->select($canSeeContact ? ['id', 'name', 'phone'] : ['id', 'name'])
            ->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'student'))
            ->where(function ($query) use ($pattern, $canSeeContact): void {
                $query->where('name', 'like', $pattern);
                if ($canSeeContact) {
                    $query->orWhere('phone', 'like', $pattern)->orWhere('email', 'like', $pattern);
                }
            })
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::RESULT_LIMIT + 1)
            ->get();

        $hasMore = $matches->count() > self::RESULT_LIMIT;

        return response()->json([
            'data' => $matches->take(self::RESULT_LIMIT)->map(fn (User $student) => [
                'id' => $student->id,
                'label' => $student->name,
                'meta' => $canSeeContact ? $student->phone : null,
            ])->values(),
            'has_more' => $hasMore,
            'limit' => self::RESULT_LIMIT,
        ])->header('Cache-Control', 'private, no-store');
    }
}
