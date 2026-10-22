<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Restrict access to routes based on the authenticated user's role.
     *
     * @param  Closure(Request): Response  $next
     * @param  string  ...$roles  roles allowed for the route (e.g. 'role:admin,warehouse_manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->deny($request);
        }

        $roleName = $user->role?->name;

        if (! in_array($roleName, $roles, true)) {
            return $this->deny($request);
        }

        return $next($request);
    }

    /**
     * Reject the request: redirect guests to login, return 403 for everyone else.
     */
    protected function deny(Request $request): Response
    {
        if ($request->user() === null) {
            return redirect()->guest(route('login'));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('You do not have permission to access this page.'),
            ], Response::HTTP_FORBIDDEN);
        }

        abort(Response::HTTP_FORBIDDEN, __('You do not have permission to access this page.'));
    }
}
