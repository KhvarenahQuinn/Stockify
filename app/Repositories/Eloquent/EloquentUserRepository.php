<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Support\Str;

class EloquentUserRepository extends BaseEloquentRepository implements UserRepository
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function toggleStatus(int $id): User
    {
        /** @var User $user */
        $user = $this->findOrFail($id);
        $user->status = $user->status === 'active' ? 'inactive' : 'active';

        if ($user->status !== 'active') {
            $user->remember_token = null;
        }

        $user->save();

        return $user->refresh();
    }

    public function updatePassword(int $id, string $hashedPassword): void
    {
        /** @var User $user */
        $user = $this->findOrFail($id);
        $user->forceFill([
            'password' => $hashedPassword,
            'remember_token' => Str::random(60),
        ])->save();
    }
}
