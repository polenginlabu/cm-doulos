<?php

namespace App\Filament\Resources\UserManagementResource\Pages;

use App\Filament\Resources\UserManagementResource;
use App\Models\Discipleship;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserManagement extends EditRecord
{
    protected static string $resource = UserManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $user = $this->record;
        $data = $this->data;

        // Helper function to create or update discipleship relationship
        $createOrUpdateDiscipleship = function ($mentorId, $fieldName = 'mentor') use ($user) {
            if (!$mentorId) {
                return true;
            }

            // Prevent self-mentorship
            if ($mentorId === $user->id) {
                \Filament\Notifications\Notification::make()
                    ->title('Invalid Selection')
                    ->body('A user cannot be their own cell leader.')
                    ->danger()
                    ->send();
                return false;
            }

            // Deactivate any existing active discipleship for this disciple (one-to-one constraint)
            $deactivated = Discipleship::where('disciple_id', $user->id)
                ->where('status', 'active')
                ->where('mentor_id', '!=', $mentorId)
                ->update(['status' => 'inactive']);

            // Check if discipleship already exists with this mentor
            $existingDiscipleship = Discipleship::where('mentor_id', $mentorId)
                ->where('disciple_id', $user->id)
                ->first();

            try {
                if (!$existingDiscipleship) {
                    // Create new discipleship relationship
                    Discipleship::create([
                        'mentor_id' => $mentorId,
                        'disciple_id' => $user->id,
                        'started_at' => now(),
                        'status' => 'active',
                    ]);
                } else {
                    // Reactivate existing relationship
                    $existingDiscipleship->update([
                        'status' => 'active',
                        'started_at' => now(),
                    ]);
                }
                return true;
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title('Error')
                    ->body('Failed to create cell leader relationship: ' . $e->getMessage())
                    ->danger()
                    ->send();
                return false;
            }
        };

        $success = true;

        // If cell_leader_id is set, create discipleship relationship (cell leader as mentor)
        if (isset($data['cell_leader_id']) && $data['cell_leader_id']) {
            if (!$createOrUpdateDiscipleship($data['cell_leader_id'], 'cell leader')) {
                $success = false;
            }
        }

        // If primary_user_id is set, create discipleship relationship (primary user as mentor)
        if (isset($data['primary_user_id']) && $data['primary_user_id']) {
            if (!$createOrUpdateDiscipleship($data['primary_user_id'], 'primary user')) {
                $success = false;
            }
        }

        if ($success) {
            \Filament\Notifications\Notification::make()
                ->title('Success')
                ->body('User updated successfully.')
                ->success()
                ->send();
        }
    }
}

