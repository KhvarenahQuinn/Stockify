<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\UpdateLocaleRequest;

/**
 * Validate the language chosen on the guest sign-in screen.
 *
 * The rules and messages are identical to the signed-in preference request;
 * only authorization differs because there is no account yet.
 */
class UpdateLoginLocaleRequest extends UpdateLocaleRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
