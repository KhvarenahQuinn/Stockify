<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepository extends BaseRepositoryInterface
{
    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Toggle the user's active/inactive status.
     */
    public function toggleStatus(int $id): User;

    /**
     * Update the user's password directly.
     */
    public function updatePassword(int $id, string $hashedPassword): void;
}
