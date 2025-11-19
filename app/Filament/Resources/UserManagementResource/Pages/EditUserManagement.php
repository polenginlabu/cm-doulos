<?php

namespace App\Filament\Resources\UserManagementResource\Pages;

use App\Filament\Resources\UserManagementResource;
use App\Models\Discipleship;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUserManagement extends EditRecord
{
    protected static string $resource = UserManagementResource::class;

    protected ?int $newPrimaryUserId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $oldPrimaryUserId = $this->record->primary_user_id;

        // Auto-link cell leader and primary user
        // If cell leader is a primary leader, set as primary user
        if (isset($data['cell_leader_id']) && $data['cell_leader_id']) {
            $cellLeader = \App\Models\User::find($data['cell_leader_id']);
            if ($cellLeader) {
                // If cell leader is a primary leader, set as primary user
                if ($cellLeader->is_primary_leader) {
                    $data['primary_user_id'] = $data['cell_leader_id'];
                }
                // Otherwise, inherit primary_user_id from cell leader
                elseif ($cellLeader->primary_user_id) {
                    $data['primary_user_id'] = $cellLeader->primary_user_id;
                }
            }
        }
        // If primary user is set, set as cell leader
        if (isset($data['primary_user_id']) && $data['primary_user_id']) {
            $data['cell_leader_id'] = $data['primary_user_id'];
        }

        // Always set gender to authenticated user's gender
        if (Auth::check()) {
            $authUser = Auth::user();
            if ($authUser->gender) {
                $data['gender'] = $authUser->gender;
            }
        }

        // Store for cascading update in afterSave
        $this->newPrimaryUserId = $data['primary_user_id'] ?? $oldPrimaryUserId;

        return $data;
    }

    protected function afterSave(): void
    {
        $user = $this->record;
        $data = $this->data;

        // Cascade primary_user_id change to all disciples
        if (isset($this->newPrimaryUserId) && $this->newPrimaryUserId != $user->getOriginal('primary_user_id')) {
            $this->cascadePrimaryUserToDisciples($user->id, $this->newPrimaryUserId);
        }

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

        // Auto-link cell leader and primary user
        // If cell leader is a primary leader, set as primary user
        if (isset($data['cell_leader_id']) && $data['cell_leader_id']) {
            $cellLeader = \App\Models\User::find($data['cell_leader_id']);
            if ($cellLeader && $cellLeader->is_primary_leader) {
                $user->primary_user_id = $data['cell_leader_id'];
                $user->save();
            }
        }
        // If primary user is set, set as cell leader
        if (isset($data['primary_user_id']) && $data['primary_user_id']) {
            $data['cell_leader_id'] = $data['primary_user_id'];
        }

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

    /**
     * Cascade primary_user_id change to all disciples recursively
     */
    protected function cascadePrimaryUserToDisciples(int $mentorId, ?int $primaryUserId): void
    {
        // Get all active disciples of this mentor
        $discipleships = \App\Models\Discipleship::where('mentor_id', $mentorId)
            ->where('status', 'active')
            ->with('disciple')
            ->get();

        foreach ($discipleships as $discipleship) {
            $disciple = $discipleship->disciple;
            if ($disciple && $disciple->primary_user_id != $primaryUserId) {
                $disciple->primary_user_id = $primaryUserId;
                $disciple->saveQuietly();

                // Recursively update their disciples
                $this->cascadePrimaryUserToDisciples($disciple->id, $primaryUserId);
            }
        }
    }
}

