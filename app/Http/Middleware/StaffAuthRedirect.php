<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;

class StaffAuthRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure($request): mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply to warehouse_staff role
        if (Auth::check() && Auth::user()?->role?->name === 'warehouse_staff') {
            // List of route prefixes that staff should not access directly
            $unauthorizedPrefixes = [
                'admin',
                'categories',
                'suppliers',
                'attributes',
                'users',
                'settings',
            ];

            $path = $request->path();
            $root = strtok($path, '?');

            // Check if the path starts with any unauthorized prefix
            foreach ($unauthorizedPrefixes as $prefix) {
                if (str_starts_with($root, $prefix)) {
                    // Redirect to dashboard with warning
                    return redirect()->route('dashboard')
                        ->with('warning', __('Staff members do not have access to this area. You have been redirected to your dashboard.'));
                }
            }
        }

        return $next($request);
    }
}