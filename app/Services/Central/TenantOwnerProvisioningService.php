<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Models\Central\Tenant;
use App\Models\Tenant\TenantUser;
use App\Notifications\Central\TenantOwnerCredentialsNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the initial store-owner user inside a provisioned tenant database.
 */
class TenantOwnerProvisioningService
{
    /**
     * @return array{user: TenantUser, password: string}|null
     */
    public function provision(Tenant $tenant): ?array
    {
        /** @var array{name: string, email: string, phone?: string|null}|null $owner */
        $owner = $tenant->owner;

        if ($owner === null) {
            return null;
        }

        tenancy()->initialize($tenant);

        try {
            if (TenantUser::query()->where('email', $owner['email'])->exists()) {
                throw new RuntimeException('A user with this owner email already exists in the tenant store.');
            }

            $password = Str::password(12);

            $user = TenantUser::query()->create([
                'name' => $owner['name'],
                'email' => $owner['email'],
                'phone' => $owner['phone'] ?? null,
                'password' => Hash::make($password),
                'is_active' => true,
            ]);

            $user->assignRole('store-owner');

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $loginUrl = $this->loginUrl($tenant);

            Notification::route('mail', $owner['email'])->notifyNow(
                new TenantOwnerCredentialsNotification($tenant, $user, $password, $loginUrl),
            );

            return [
                'user' => $user,
                'password' => $password,
            ];
        } finally {
            tenancy()->end();
        }
    }

    private function loginUrl(Tenant $tenant): string
    {
        $tenant->loadMissing('primaryDomain');

        $host = $tenant->primaryDomain?->full_domain ?? $tenant->id.'.'.config('app.tenant_base_domain');

        return 'https://'.$host.'/api/v1/tenant/auth/login';
    }
}
