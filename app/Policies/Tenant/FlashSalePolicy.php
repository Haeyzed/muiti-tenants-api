<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\FlashSale;
use App\Models\Tenant\TenantUser;

/**
 * Authorization rules for flash sale management.
 */
class FlashSalePolicy
{
    public function viewAny(TenantUser $user): bool
    {
        return $user->can('flash-sales.view');
    }

    public function view(TenantUser $user, FlashSale $flashSale): bool
    {
        return $user->can('flash-sales.view');
    }

    public function create(TenantUser $user): bool
    {
        return $user->can('flash-sales.create');
    }

    public function update(TenantUser $user, FlashSale $flashSale): bool
    {
        return $user->can('flash-sales.update');
    }

    public function delete(TenantUser $user, FlashSale $flashSale): bool
    {
        return $user->can('flash-sales.delete');
    }

    public function manage(TenantUser $user, FlashSale $flashSale): bool
    {
        return $user->can('flash-sales.manage');
    }
}
