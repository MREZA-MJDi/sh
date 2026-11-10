<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        $allowed = $user && collect($permissions)->contains(
            fn (string $permission): bool => $user->hasPermissionTo($permission)
        );

        abort_unless($allowed, 403, 'شما مجوز انجام این عملیات را ندارید.');

        return $next($request);
    }
}
