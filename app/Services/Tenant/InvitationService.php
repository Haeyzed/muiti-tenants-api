<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Events\Tenant\TeamInvitationSent;
use App\Models\Tenant\TeamInvitation;
use App\Models\Tenant\TenantUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Manages team member invitations.
 */
class InvitationService
{
    /**
     * Paginate team invitations.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, TeamInvitation>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TeamInvitation::query()
            ->with('inviter')
            ->latest();

        if (!empty($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        if (!empty($filters['status'])) {
            match ($filters['status']) {
                'pending' => $query->whereNull('accepted_at')->whereNull('cancelled_at')->where('expires_at', '>', now()),
                'accepted' => $query->whereNotNull('accepted_at'),
                'cancelled' => $query->whereNotNull('cancelled_at'),
                'expired' => $query->whereNull('accepted_at')->where('expires_at', '<=', now()),
                default => null,
            };
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a team invitation by ID.
     *
     * @param int $id
     * @return TeamInvitation
     */
    public function find(int $id): TeamInvitation
    {
        return TeamInvitation::query()
            ->with('inviter')
            ->findOrFail($id);
    }

    /**
     * Find a team invitation by token.
     *
     * @param string $token
     * @return TeamInvitation
     */
    public function findByToken(string $token): TeamInvitation
    {
        return TeamInvitation::query()
            ->where('token', $token)
            ->firstOrFail();
    }

    /**
     * Send a new team invitation.
     *
     * @param TenantUser $inviter
     * @param array<string, mixed> $data
     * @param int $expiresInDays
     * @return TeamInvitation
     * @throws Throwable
     */
    public function send(TenantUser $inviter, array $data, int $expiresInDays = 7): TeamInvitation
    {
        return DB::transaction(function () use ($inviter, $data, $expiresInDays): TeamInvitation {
            $invitation = TeamInvitation::query()->create([
                'email' => $data['email'],
                'token' => $this->generateToken(),
                'role' => $data['role'],
                'permissions' => $data['permissions'] ?? null,
                'invited_by' => $inviter->id,
                'expires_at' => now()->addDays($expiresInDays),
            ]);

            TeamInvitationSent::dispatch($invitation->load('inviter'));

            return $invitation;
        });
    }

    /**
     * Accept a team invitation.
     *
     * @param TeamInvitation $invitation
     * @param array<string, mixed> $userData
     * @return TenantUser
     * @throws Throwable
     */
    public function accept(TeamInvitation $invitation, array $userData): TenantUser
    {
        return DB::transaction(function () use ($invitation, $userData): TenantUser {
            if (!$invitation->isPending()) {
                throw new RuntimeException('Invitation is no longer valid.');
            }

            $user = TenantUser::query()->create([
                'name' => $userData['name'],
                'email' => $invitation->email,
                'password' => $userData['password'],
                'is_active' => true,
            ]);

            $user->assignRole($invitation->role);

            $invitation->update(['accepted_at' => now()]);

            return $user->load('roles');
        });
    }

    /**
     * Resend a team invitation.
     *
     * @param TeamInvitation $invitation
     * @param int $expiresInDays
     * @return TeamInvitation
     * @throws Throwable
     */
    public function resend(TeamInvitation $invitation, int $expiresInDays = 7): TeamInvitation
    {
        return DB::transaction(function () use ($invitation, $expiresInDays): TeamInvitation {
            if ($invitation->accepted_at !== null) {
                throw new RuntimeException('Cannot resend an accepted invitation.');
            }

            $invitation->update([
                'token' => $this->generateToken(),
                'expires_at' => now()->addDays($expiresInDays),
                'cancelled_at' => null,
            ]);

            TeamInvitationSent::dispatch($invitation->fresh(['inviter']));

            return $invitation->fresh();
        });
    }

    /**
     * Cancel a team invitation.
     *
     * @param TeamInvitation $invitation
     * @return TeamInvitation
     */
    public function cancel(TeamInvitation $invitation): TeamInvitation
    {
        $invitation->update(['cancelled_at' => now()]);

        return $invitation->fresh();
    }

    /**
     * Delete a team invitation.
     *
     * @param TeamInvitation $invitation
     * @return void
     */
    public function delete(TeamInvitation $invitation): void
    {
        $invitation->delete();
    }

    /**
     * Delete multiple team invitations by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return TeamInvitation::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete a team invitation permanently.
     *
     * @param TeamInvitation $invitation
     * @return void
     */
    public function forceDelete(TeamInvitation $invitation): void
    {
        $invitation->forceDelete();
    }

    /**
     * Restore a soft-deleted team invitation.
     *
     * @param TeamInvitation $invitation
     * @return TeamInvitation
     */
    public function restore(TeamInvitation $invitation): TeamInvitation
    {
        $invitation->restore();

        return $invitation->fresh();
    }

    /**
     * Restore multiple soft-deleted team invitations by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return TeamInvitation::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Expire a team invitation.
     *
     * @param TeamInvitation $invitation
     * @return TeamInvitation
     */
    public function expire(TeamInvitation $invitation): TeamInvitation
    {
        $invitation->update(['expires_at' => now()]);

        return $invitation->fresh();
    }

    /**
     * Generate a new invitation token.
     *
     * @return string
     */
    private function generateToken(): string
    {
        return Str::random(64);
    }
}
