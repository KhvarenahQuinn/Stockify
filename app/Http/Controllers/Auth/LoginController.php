<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Display the login view.
     *
     * The shared example header renders {{ $title }}, so it has to be supplied
     * here as every other page does.
     */
    public function create(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        return view('auth.login', ['title' => __('Sign in')]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @throws ValidationException
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $credentials = [
            ...$request->only('email', 'password'),
            // WORKFLOW.md treats a deactivated account as locked out, so the
            // status belongs in the credential check rather than only in the
            // admin UI. Folding it in means an inactive account fails with
            // exactly the same message as a wrong password, so the form never
            // reveals which addresses exist.
            'status' => 'active',
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->hitRateLimiter();

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->clearRateLimiter();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
