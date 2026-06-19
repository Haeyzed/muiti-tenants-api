<?php

declare(strict_types=1);

namespace App\Providers;

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class ScrambleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Scramble::ignoreDefaultRoutes();
    }

    public function boot(): void
    {
        $this->registerCentralApiDocs();
        $this->registerTenantApiDocs();
    }

    private function registerCentralApiDocs(): void
    {
        Scramble::registerApi('central', [
            'api_path' => 'api/v1/central',
            'info' => [
                'version' => config('scramble.info.version'),
                'description' => 'Platform administration API for tenants, plans, and billing.',
            ],
            'ui' => [
                'title' => 'Central API',
            ],
            'middleware' => [
                'web',
                RestrictedDocsAccess::class,
            ],
            'security_strategy' => $this->securityStrategy(
                'Sanctum bearer token. Obtain a token from `POST /api/v1/central/auth/login`.',
            ),
        ])
            ->routes(fn (Route $route): bool => Str::startsWith($route->uri(), 'api/v1/central'))
            ->expose(
                ui: 'docs/central',
                document: 'docs/central.json',
            );
    }

    private function registerTenantApiDocs(): void
    {
        Scramble::registerApi('tenant', [
            'api_path' => 'api/v1/tenant',
            'info' => [
                'version' => config('scramble.info.version'),
                'description' => 'Storefront and back-office API for an individual tenant store.',
            ],
            'ui' => [
                'title' => 'Tenant API',
            ],
            'middleware' => [
                'web',
                InitializeTenancyByDomainOrSubdomain::class,
                PreventAccessFromCentralDomains::class,
                RestrictedDocsAccess::class,
            ],
            'security_strategy' => $this->securityStrategy(
                'Sanctum bearer token. Staff: `POST /api/v1/tenant/auth/login`. Customers: `POST /api/v1/tenant/customer/auth/login`.',
            ),
        ])
            ->routes(fn (Route $route): bool => Str::startsWith($route->uri(), 'api/v1/tenant'))
            ->expose(
                ui: 'docs/tenant',
                document: 'docs/tenant.json',
            );
    }

    /**
     * @return array{0: class-string<MiddlewareAuthSecurityStrategy>, 1: array<string, mixed>}
     */
    private function securityStrategy(string $description): array
    {
        return [
            MiddlewareAuthSecurityStrategy::class,
            [
                'middleware' => ['auth', 'auth:*'],
                'scheme' => SecurityScheme::http('bearer', 'Token')
                    ->as('bearerAuth')
                    ->setDescription($description),
            ],
        ];
    }
}
