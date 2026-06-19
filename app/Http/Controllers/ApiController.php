<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ApiResponse;

/**
 * Base API controller providing standardized JSON responses.
 */
abstract class ApiController extends Controller
{
    use ApiResponse;
}
