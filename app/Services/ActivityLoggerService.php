<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLoggerService
{
    public function __construct(
        protected readonly Request $request,
    ) {
    }

    /**
     * Record an action into the activity_logs table.
     *
     * @param User|int|null $user  The user performing the action (or null for system actions)
     * @param string $action       A short slug describing the action (e.g. 'user.created')
     * @param string $description  Human-readable description of what happened
     */
    public function log(User|int|null $user, string $action, string $description): ActivityLog
    {
        $userId = $user instanceof User ? $user->id : $user;

        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $this->request->ip() ?? '0.0.0.0',
        ]);
    }
}
