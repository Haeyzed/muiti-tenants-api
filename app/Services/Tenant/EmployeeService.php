<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\EmergencyContact;
use App\Models\Tenant\EmployeeProfile;
use App\Models\Tenant\Staff;
use App\Models\Tenant\StaffDocument;
use App\Models\Tenant\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages employee profiles, documents, and emergency contacts.
 */
class EmployeeService
{
    /**
     * Upsert an employee profile.
     *
     * @param Staff $staff
     * @param array<string, mixed> $data
     * @return EmployeeProfile
     * @throws Throwable
     */
    public function upsertProfile(Staff $staff, array $data): EmployeeProfile
    {
        return DB::transaction(function () use ($staff, $data): Model {
            return $staff->profile()->updateOrCreate(
                ['staff_id' => $staff->id],
                $data,
            );
        });
    }

    /**
     * Add an emergency contact for an employee.
     *
     * @param Staff $staff
     * @param array<string, mixed> $data
     * @return EmergencyContact
     * @throws Throwable
     */
    public function addEmergencyContact(Staff $staff, array $data): EmergencyContact
    {
        return DB::transaction(function () use ($staff, $data): EmergencyContact {
            if (!empty($data['is_primary'])) {
                $staff->emergencyContacts()->update(['is_primary' => false]);
            }

            return $staff->emergencyContacts()->create($data);
        });
    }

    /**
     * Update an emergency contact.
     *
     * @param EmergencyContact $contact
     * @param array<string, mixed> $data
     * @return EmergencyContact
     * @throws Throwable
     */
    public function updateEmergencyContact(EmergencyContact $contact, array $data): EmergencyContact
    {
        return DB::transaction(function () use ($contact, $data): EmergencyContact {
            if (!empty($data['is_primary'])) {
                $contact->staff->emergencyContacts()
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);
            }

            $contact->update($data);

            return $contact->fresh();
        });
    }

    /**
     * Delete an emergency contact.
     *
     * @param EmergencyContact $contact
     * @return void
     */
    public function deleteEmergencyContact(EmergencyContact $contact): void
    {
        $contact->delete();
    }

    /**
     * Delete multiple emergency contacts by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteManyEmergencyContacts(array $ids): int
    {
        return EmergencyContact::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete an emergency contact permanently.
     *
     * @param EmergencyContact $contact
     * @return void
     */
    public function forceDeleteEmergencyContact(EmergencyContact $contact): void
    {
        $contact->forceDelete();
    }

    /**
     * Restore a soft-deleted emergency contact.
     *
     * @param EmergencyContact $contact
     * @return EmergencyContact
     */
    public function restoreEmergencyContact(EmergencyContact $contact): EmergencyContact
    {
        $contact->restore();

        return $contact->fresh();
    }

    /**
     * Restore multiple soft-deleted emergency contacts by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreManyEmergencyContacts(array $ids): int
    {
        return EmergencyContact::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Upload a document for an employee.
     *
     * @param Staff $staff
     * @param array<string, mixed> $data
     * @param UploadedFile $file
     * @param TenantUser|null $uploadedBy
     * @return StaffDocument
     * @throws Throwable
     */
    public function uploadDocument(
        Staff        $staff,
        array        $data,
        UploadedFile $file,
        ?TenantUser  $uploadedBy = null,
    ): StaffDocument
    {
        return DB::transaction(function () use ($staff, $data, $file, $uploadedBy): StaffDocument {
            $document = $staff->documents()->create([
                ...$data,
                'uploaded_by' => $uploadedBy?->id,
            ]);

            $document->addMedia($file)->toMediaCollection('staff_documents');

            return $document->load('media');
        });
    }

    /**
     * Delete an employee document.
     *
     * @param StaffDocument $document
     * @return void
     */
    public function deleteDocument(StaffDocument $document): void
    {
        $document->delete();
    }

    /**
     * Delete multiple employee documents by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function deleteManyDocuments(array $ids): int
    {
        return StaffDocument::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Force delete an employee document permanently.
     *
     * @param StaffDocument $document
     * @return void
     */
    public function forceDeleteDocument(StaffDocument $document): void
    {
        $document->forceDelete();
    }

    /**
     * Restore a soft-deleted employee document.
     *
     * @param StaffDocument $document
     * @return StaffDocument
     */
    public function restoreDocument(StaffDocument $document): StaffDocument
    {
        $document->restore();

        return $document->fresh();
    }

    /**
     * Restore multiple soft-deleted employee documents by ID.
     *
     * @param list<int> $ids
     * @return int
     */
    public function restoreManyDocuments(array $ids): int
    {
        return StaffDocument::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }
}
