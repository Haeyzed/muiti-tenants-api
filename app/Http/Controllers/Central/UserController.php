<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Exports\Central\UsersExport;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Central\StoreUserRequest;
use App\Http\Requests\Central\UpdateUserRequest;
use App\Http\Resources\Central\CentralUserResource;
use App\Imports\Central\UsersImport;
use App\Models\Central\CentralUser;
use App\Services\Central\ExcelExportService;
use App\Services\Central\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Manages central platform administrator accounts.
 */
class UserController extends ApiController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly ExcelExportService $excelExportService,
    ) {}

    /**
     * Get a paginated list of users.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CentralUser::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'array'],
            'is_active.*' => ['string', 'in:active,inactive'],
        ]);

        $users = $this->userService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($users, CentralUserResource::collection($users), 'Users retrieved successfully.');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', CentralUser::class);

        $user = $this->userService->create($request->validated());

        return $this->created(
            new CentralUserResource($user),
            'User created successfully.',
        );
    }

    /**
     * Display the specified user.
     */
    public function show(CentralUser $user): JsonResponse
    {
        $this->authorize('view', $user);

        return $this->success(new CentralUserResource($this->userService->find($user->id)), 'User retrieved successfully.');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, CentralUser $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->userService->update($user, $request->validated());

        return $this->updated(
            new CentralUserResource($user),
            'User updated successfully.',
        );
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(CentralUser $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return $this->deleted('User deleted successfully.');
    }

    /**
     * Get user statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', CentralUser::class);

        return $this->success($this->userService->statistics(), 'User statistics retrieved successfully.');
    }

    /**
     * Get user options.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', CentralUser::class);

        return $this->success($this->userService->getOptions(), 'User options retrieved successfully.');
    }

    /**
     * Delete multiple users.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', CentralUser::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $count = $this->userService->deleteMany($validated['ids'], (int) $request->user()?->id);

        return $this->success(null, "{$count} users deleted successfully.");
    }

    /**
     * Export users to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', CentralUser::class);

        $validated = $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'exists:users,id'],
            'delivery' => ['sometimes', 'in:download,email'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'recipient_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $users = $this->userService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new UsersExport($users);
        $filename = 'users-export.xlsx';

        if (($validated['delivery'] ?? 'download') === 'email') {
            $content = $this->excelExportService->raw($export);
            $recipient = isset($validated['recipient_id'])
                ? CentralUser::query()->findOrFail($validated['recipient_id'])
                : $request->user();

            Mail::raw('Your users export is attached.', function ($message) use ($recipient, $content, $filename): void {
                $message->to($recipient->email)
                    ->subject('Users Export')
                    ->attachData(
                        $content,
                        $filename,
                        ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                    );
            });

            return $this->success(null, 'Export sent successfully.');
        }

        return $this->excelExportService->download($export, $filename);
    }

    /**
     * Import users from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', CentralUser::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new UsersImport, $request->file('file'));

        return $this->success(null, 'Users imported successfully.');
    }
}
