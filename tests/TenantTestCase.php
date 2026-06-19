<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;

abstract class TenantTestCase extends TestCase
{
    use DatabaseMigrations;
}
