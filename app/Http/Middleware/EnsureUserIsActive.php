<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Revokes access the moment an account is deactivated.
 *
 * Blocking this at login is not enough: a session issued while the account was
 * active stays valid until it expires, so deactivating someone in User
 * Management would otherwise not actually lock them out.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // The 'auth' alias runs Authenticate first, so a null here means the
        // request is already on its way to the login screen.
        if ($user === null) {
            return $next($request);
        }

        if ($user->status === 'active') {
            return $next($request);
        }

        Auth::logout();

        // Invalidate rather than just logout: the old session id must not stay
        // usable, and the CSRF token has to be rotated with it.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = __('Your account has been deactivated. Contact an administrator.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('login')->with('error', $message);
    }
}