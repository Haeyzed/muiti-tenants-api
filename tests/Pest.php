<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature/Central');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature/ExampleTest.php');

pest()->extend(Tests\TenantTestCase::class)
    ->in('Feature/Tenant');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Provision a tenant context for feature tests.
 */
function initializeTenantForTest(string $role = 'store-owner'): object
{
    $tenant = \App\Models\Central\Tenant::factory()->create([
        'name' => 'Test Store',
    ]);

    $slug = 'test-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
    $domain = $slug.'.multi-tenants-api.test';

    $tenant->domains()->create([
        'domain' => $slug,
        'is_primary' => true,
        'verification_status' => \App\Enums\Central\DomainVerificationStatus::Verified,
        'verified_at' => now(),
    ]);

    tenancy()->initialize($tenant);

    (new \Database\Seeders\TenantRolePermissionSeeder())->run();

    $user = \App\Models\Tenant\TenantUser::factory()->create([
        'password' => bcrypt('password'),
    ]);
    $user->assignRole($role);

    if ($role === 'customer') {
        \App\Models\Tenant\Customer::factory()->create([
            'user_id' => $user->id,
            'first_name' => explode(' ', $user->name)[0] ?? 'Test',
            'last_name' => explode(' ', $user->name)[1] ?? 'Customer',
            'email' => $user->email,
        ]);
    }

    $token = $user->createToken('test')->plainTextToken;

    tenancy()->end();

    return (object) [
        'tenant' => $tenant,
        'domain' => $domain,
        'user' => $user,
        'token' => $token,
    ];
}
