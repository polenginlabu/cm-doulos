<?php

namespace App\Filament\Resources\TrainingEnrollmentResource\Pages;

use App\Filament\Resources\TrainingEnrollmentResource;
use App\Models\TrainingEnrollment;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateTrainingEnrollment extends CreateRecord
{
    protected static string $resource = TrainingEnrollmentResource::class;

    protected bool $recordsAlreadyCreated = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle multiple user enrollments
        $userIds = $data['user_ids'] ?? [];
        $trainingId = $data['training_id'] ?? null;
        $batchId = $data['batch_id'] ?? null;
        $enrolledAt = $data['enrolled_at'] ?? now();
        $status = $data['status'] ?? 'enrolled';
        $notes = $data['notes'] ?? null;

        // If batch is selected, get training_id from batch
        if ($batchId && !$trainingId) {
            $batch = \App\Models\TrainingBatch::find($batchId);
            if ($batch) {
                $trainingId = $batch->training_id;
            }
        }

        // Validate training_id is present
        if (!$trainingId) {
            throw ValidationException::withMessages([
                'training_id' => 'Please select a training program.',
            ]);
        }

        // Validate batch_id is present (required for all trainings)
        if (!$batchId) {
            throw ValidationException::withMessages([
                'batch_id' => 'Please select a training batch.',
            ]);
        }

        // If user_ids is not an array, make it an array
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        // Validate that at least one user is selected
        if (empty($userIds)) {
            throw ValidationException::withMessages([
                'user_ids' => 'Please select at least one student to enroll.',
            ]);
        }

        $enrolled = 0;
        $skipped = 0;

        foreach ($userIds as $userId) {
            // Check if already enrolled in this batch
            $existing = TrainingEnrollment::where('user_id', $userId)
                ->where('training_id', $trainingId)
                ->where('batch_id', $batchId)
                ->where('status', '!=', 'dropped')
                ->exists();

            if ($existing) {
                $skipped++;
                continue;
            }

            // Create enrollment
            TrainingEnrollment::create([
                'training_id' => $trainingId,
                'batch_id' => $batchId,
                'user_id' => $userId,
                'enrolled_at' => $enrolledAt,
                'status' => $status,
                'notes' => $notes,
            ]);

            $enrolled++;
        }

        // Mark that we've already created the records
        if ($enrolled > 0) {
            $this->recordsAlreadyCreated = true;

            // Show success notification
            $message = "Successfully enrolled {$enrolled} student(s).";
            if ($skipped > 0) {
                $message .= " {$skipped} student(s) were skipped (already enrolled).";
            }

            Notification::make()
                ->title($message)
                ->success()
                ->send();

            // Return data for the first enrollment (won't be used since recordsAlreadyCreated is true)
            return [
                'training_id' => $trainingId,
                'batch_id' => $batchId,
                'user_id' => $userIds[0],
                'enrolled_at' => $enrolledAt,
                'status' => $status,
                'notes' => $notes,
            ];
        }

        // If no enrollments were created (all skipped)
        if ($skipped > 0) {
            Notification::make()
                ->title('No enrollments created')
                ->body("All {$skipped} student(s) were already enrolled in this batch.")
                ->warning()
                ->send();

            // Prevent creation
            $this->recordsAlreadyCreated = true;
            throw ValidationException::withMessages([
                'user_ids' => 'All selected students are already enrolled in this batch.',
            ]);
        }

        // Should not reach here, but just in case
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // If we've already created the records manually, return a dummy model
        if ($this->recordsAlreadyCreated) {
            return new TrainingEnrollment($data);
        }

        // This should not happen for create page (we always handle multiple users)
        // But keep it as fallback
        return parent::handleRecordCreation($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

