<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Return a paginated JSON payload.
     *
     * @param LengthAwarePaginator<int, mixed> $paginator Paginated query result.
     * @param mixed $data Resource collection for the current page.
     * @param string|null $message Optional human-readable status message.
     */
    protected function paginated(LengthAwarePaginator $paginator, mixed $data, ?string $message = null): JsonResponse
    {
        $payload = [
            'success' => true,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        $payload['data'] = $data;
        $payload['meta'] = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];

        return response()->json($payload);
    }

    /**
     * Return a created resource response.
     *
     * @param mixed $data Created resource payload.
     * @param string $message Human-readable status message.
     */
    protected function created(mixed $data, string $message): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a JSON payload with optional message and data.
     *
     * @param mixed $data Resource, collection, or array payload.
     * @param string|null $message Human-readable status message.
     * @param int $status HTTP status code.
     */
    protected function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data instanceof JsonResource ? $data : $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return an updated resource response.
     *
     * @param mixed $data Updated resource payload.
     * @param string $message Human-readable status message.
     */
    protected function updated(mixed $data, string $message): JsonResponse
    {
        return $this->success($data, $message);
    }

    /**
     * Return a deleted resource response.
     *
     * @param string $message Human-readable status message.
     */
    protected function deleted(string $message): JsonResponse
    {
        return $this->success(message: $message);
    }

    /**
 * Return a generic error JSON response.
 *
 * @param string $message Human-readable error message.
 * @param int $status HTTP status code.
 * @param mixed|null $errors Additional error details.
 */
    protected function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return a 400 Bad Request error.
     */
    protected function badRequest(string $message = 'Bad Request', mixed $errors = null): JsonResponse
    {
        return $this->error($message, 400, $errors);
    }

    /**
     * Return a 401 Unauthorized error.
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Return a 403 Forbidden error.
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Return a 404 Not Found error.
     */
    protected function notFound(string $message = 'Resource Not Found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return a 422 Unprocessable Entity (Validation) error.
     *
     * @param mixed $errors Validation error details.
     */
    protected function validationError(mixed $errors, string $message = 'Validation Failed'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Return a 500 Internal Server Error.
     *
     * @param string $message Human-readable error message.
     * @param mixed|null $details Optional technical details (e.g., exception trace for debugging).
     */
    protected function serverError(string $message = 'Internal Server Error', mixed $details = null): JsonResponse
    {
        return $this->error($message, 500, $details);
    }
}
