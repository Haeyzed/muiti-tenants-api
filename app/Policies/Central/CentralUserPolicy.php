<?php

declare(strict_types=1);

namespace App\Policies\Central;

use App\Models\Central\CentralUser;

/**
 * Authorization rules for central user management.
 */
class CentralUserPolicy
{
    public function viewAny(CentralUser $user): bool
    {
        return $user->can('users.view');
    }

    public function view(CentralUser $user, CentralUser $model): bool
    {
        return $user->can('users.view');
    }

    public function create(CentralUser $user): bool
    {
        return $user->can('users.create');
    }

    public function update(CentralUser $user, CentralUser $model): bool
    {
        return $user->can('users.update');
    }

    public function delete(CentralUser $user, CentralUser $model): bool
    {
        return $user->can('users.delete') && $user->id !== $model->id;
    }

    public function deleteAny(CentralUser $user): bool
    {
        return $user->can('users.delete');
    }
}
