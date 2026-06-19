<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Enums\Central\DomainVerificationStatus;
use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Manages tenant domain registration and verification.
 */
class DomainService
{
    /**
     * Create a subdomain for the tenant on the platform base domain.
     *
     * @param Tenant $tenant
     * @param string $subdomain
     * @param bool $isPrimary
     * @return Domain
     * @throws Throwable
     */
    public function createSubdomain(Tenant $tenant, string $subdomain, bool $isPrimary = false): Domain
    {
        return $this->create($tenant, Str::lower($subdomain), $isPrimary, verified: true);
    }

    /**
     * Register a custom domain for a tenant.
     *
     * @param Tenant $tenant
     * @param string $domain
     * @param bool $isPrimary
     * @return Domain
     * @throws Throwable
     */
    public function createCustomDomain(Tenant $tenant, string $domain, bool $isPrimary = false): Domain
    {
        return $this->create($tenant, Str::lower($domain), $isPrimary, verified: false);
    }

    /**
     * Verify a custom domain.
     *
     * @param Domain $domain
     * @return Domain
     * @throws ValidationException
     */
    public function verify(Domain $domain): Domain
    {
        // 1. If we are NOT in the local environment, perform the actual DNS check.
        if (! App::environment('local')) {
            $isVerified = $this->verifyDnsTxtRecord($domain->domain, (string) $domain->verification_token);

            if (! $isVerified) {
                throw ValidationException::withMessages([
                    'domain' => 'We could not verify the DNS TXT record. DNS changes can take up to 24 hours to propagate, but usually take a few minutes. Please check your settings and try again.',
                ]);
            }
        }

        // 2. Update the database (Runs instantly on local, or after successful DNS check in production)
        $domain->update([
            'verification_status' => DomainVerificationStatus::Verified,
            'verified_at' => now(),
            'verification_token' => null,
        ]);

        return $domain->fresh();
    }

    /**
     * Delete a domain.
     *
     * @param Domain $domain
     * @return void
     */
    public function delete(Domain $domain): void
    {
        $domain->delete();
    }

    /**
     * Create a domain record.
     *
     * @param Tenant $tenant
     * @param string $domain
     * @param bool $isPrimary
     * @param bool $verified
     * @return Domain
     * @throws Throwable
     */
    private function create(Tenant $tenant, string $domain, bool $isPrimary, bool $verified): Domain
    {
        return DB::transaction(function () use ($tenant, $domain, $isPrimary, $verified): Domain {
            if ($isPrimary) {
                $tenant->domains()->update(['is_primary' => false]);
            }

            return $tenant->domains()->create([
                'domain' => $domain,
                'is_primary' => $isPrimary,
                'verification_status' => $verified
                    ? DomainVerificationStatus::Verified
                    : DomainVerificationStatus::Pending,
                'verification_token' => $verified ? null : Str::random(32),
                'verified_at' => $verified ? now() : null,
            ]);
        });
    }

    /**
     * Query the live internet for the domain's TXT records and check for our token.
     *
     * @param string $domainName
     * @param string $token
     * @return bool
     */
    private function verifyDnsTxtRecord(string $domainName, string $token): bool
    {
        // The @ suppresses warnings if the DNS lookup completely fails (e.g., domain doesn't exist yet)
        $records = @dns_get_record($domainName, DNS_TXT);

        if ($records === false) {
            return false;
        }

        foreach ($records as $record) {
            // Standard TXT record check
            if (isset($record['txt']) && $record['txt'] === $token) {
                return true;
            }

            // Some DNS providers return multi-part TXT records in an 'entries' array
            if (isset($record['entries']) && in_array($token, $record['entries'], true)) {
                return true;
            }
        }

        return false;
    }
}
