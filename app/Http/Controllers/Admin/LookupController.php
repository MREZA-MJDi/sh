<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Models\\User;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

final class LookupController extends Controller
{
    private const RESULT_LIMIT = 20;

    public function students(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermissionTo('users.manage'), 403);

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $term = trim($validated['q']);

        $matches = User::query()
            ->select(['id', 'name', 'phone'])
            ->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'student'))
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', '%' . addcslashes($term, '%_\\\\') . '%')
                    ->orWhere('phone', 'like', '%' . addcslashes($term, '%_\\\\') . '%')
                    ->orWhere('email', 'like', '%' . addcslashes($term, '%_\\\\') . '%');
            })
            ->orderBy('name')
            ->limit(self::RESULT_LIMIT + 1)
            ->get();

        $hasMore = $matches->count() > self::RESULT_LIMIT;

        return response()->json([
            'data' => $matches->take(self::RESULT_LIMIT)->map(fn (User $student) => [
                'id' => $student->id,
                'label' => $student->name,
                'meta' => $student->phone,
            ])->values(),
            'has_more' => $hasMore,
            'limit' => self::RESULT_LIMIT,
        ])->header('Cache-Control', 'private, no-store');
    }
}
