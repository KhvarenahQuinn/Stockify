<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLoggerService
{
    /**
     * Columns that must never reach the audit payload: primary keys and
     * timestamps are noise, and credentials would leak secrets into a table
     * that managers and admins can read.
     *
     * @var array<int, string>
     */
    protected const REDACTED_ATTRIBUTES = [
        'id',
        'password',
        'remember_token',
        'email_verified_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function __construct(
        protected readonly Request $request,
    ) {}

    /**
     * Record an action into the activity_logs table.
     *
     * @param  User|int|null  $user  The user performing the action (or null for system actions)
     * @param  string  $action  A short slug describing the action (e.g. 'user.created')
     * @param  string  $description  Human-readable description of what happened
     * @param  array  $properties  Old and new values payload (e.g. ['old' => [...], 'new' => [...]])
     * @param  string|null  $subject  Human label of the record acted on (a user or product name)
     */
    public function log(User|int|null $user, string $action, string $description, array $properties = [], ?string $subject = null): ActivityLog
    {
        $userId = $user instanceof User ? $user->id : $user;

        if (filled($subject)) {
            $properties = ['subject' => $subject] + $properties;
        }

        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $this->request->ip() ?? '0.0.0.0',
            'properties' => $properties === [] ? null : $properties,
        ]);
    }

    /**
     * Capture a model's raw columns before an update so {@see diff()} has a
     * stable "before" to compare against. Raw attributes are used so a cast
     * (dates, booleans, money) cannot make an unchanged value look dirty.
     *
     * @param  array<int, string>  $except  Extra columns to omit from the payload.
     * @return array<string, mixed>
     */
    public function snapshot(Model $model, array $except = []): array
    {
        return array_diff_key(
            $model->getRawOriginal(),
            array_flip(array_merge(self::REDACTED_ATTRIBUTES, $except))
        );
    }

    /**
     * Build the `['old' => [...], 'new' => [...]]` payload for an update.
     *
     * Only columns whose value actually moved are kept, so the detail modal
     * shows the edit rather than every column on the row. Returns an empty
     * array when nothing changed, which stores no payload at all.
     *
     * @param  array<string, mixed>  $before  From {@see snapshot()}.
     * @param  array<int, string>  $except
     * @return array{old: array<string, mixed>, new: array<string, mixed>}|array{}
     */
    public function diff(array $before, Model $after, array $except = []): array
    {
        $old = [];
        $new = [];

        foreach ($this->snapshot($after, $except) as $field => $value) {
            if (! array_key_exists($field, $before) || $this->sameValue($before[$field], $value)) {
                continue;
            }

            $old[$field] = $before[$field];
            $new[$field] = $value;
        }

        return $old === [] ? [] : ['old' => $old, 'new' => $new];
    }

    /**
     * Loose comparison: the database hands back strings for numbers and
     * booleans, while Eloquent casts may hand back natives.
     */
    protected function sameValue(mixed $first, mixed $second): bool
    {
        if ($first === null || $second === null) {
            return $first === $second;
        }

        if (is_scalar($first) && is_scalar($second)) {
            return (string) $first === (string) $second;
        }

        return $first == $second;
    }
}
