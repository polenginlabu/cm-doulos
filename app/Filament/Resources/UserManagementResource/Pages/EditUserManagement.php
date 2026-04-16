<?php

namespace App\Filament\Resources\UserManagementResource\Pages;

use App\Filament\Resources\UserManagementResource;
use App\Models\Discipleship;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        if (isset($data['cell_leader_id']) && $data['cell_leader_id']) {
            $cellLeader = \App\Models\User::find($data['cell_leader_id']);
            if ($cellLeader) {
                if ($cellLeader->is_primary_leader) {
                    $data['primary_user_id'] = $data['cell_leader_id'];
                } elseif ($cellLeader->primary_user_id) {
                    $data['primary_user_id'] = $cellLeader->primary_user_id;
                }
            }
        } elseif (isset($data['primary_user_id']) && $data['primary_user_id'] && empty($data['cell_leader_id'])) {
            // Only use primary_user_id as cell leader if no cell leader was explicitly set
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

        // A disciple can ONLY have ONE active mentor at a time
        // cell_leader_id is the authoritative mentor; fall back to primary_user_id
        $mentorId = $data['cell_leader_id'] ?? $data['primary_user_id'] ?? null;

        if ($mentorId) {
            // Prevent self-mentorship (cast to int for reliable comparison)
            if ((int) $mentorId === (int) $user->id) {
                \Filament\Notifications\Notification::make()
                    ->title('Invalid Selection')
                    ->body('A user cannot be their own cell leader.')
                    ->danger()
                    ->send();
                return;
            }

            try {
                DB::transaction(function () use ($user, $mentorId) {
                    // Deactivate ALL existing active discipleships for this disciple
                    Discipleship::where('disciple_id', $user->id)
                        ->where('status', 'active')
                        ->update(['status' => 'inactive']);

                    // Check if discipleship already exists with this mentor
                    $existingDiscipleship = Discipleship::where('mentor_id', $mentorId)
                        ->where('disciple_id', $user->id)
                        ->lockForUpdate()
                        ->first();

                    if ($existingDiscipleship) {
                        $existingDiscipleship->update([
                            'status' => 'active',
                            'started_at' => $existingDiscipleship->started_at ?? now(),
                        ]);
                    } else {
                        Discipleship::create([
                            'mentor_id' => $mentorId,
                            'disciple_id' => $user->id,
                            'started_at' => now(),
                            'status' => 'active',
                        ]);
                    }
                });
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title('Error')
                    ->body('Failed to update cell leader relationship: ' . $e->getMessage())
                    ->danger()
                    ->send();
                return;
            }
        }

        \Filament\Notifications\Notification::make()
            ->title('Success')
            ->body('User updated successfully.')
            ->success()
            ->send();
    }

    /**
     * Cascade primary_user_id change to all disciples recursively.
     */
    protected function cascadePrimaryUserToDisciples(int $mentorId, ?int $primaryUserId, int $depth = 0): void
    {
        if ($depth >= 50) {
            return;
        }

        $discipleships = \App\Models\Discipleship::where('mentor_id', $mentorId)
            ->where('status', 'active')
            ->with('disciple')
            ->get();

        foreach ($discipleships as $discipleship) {
            $disciple = $discipleship->disciple;
            if ($disciple && $disciple->primary_user_id != $primaryUserId) {
                $disciple->primary_user_id = $primaryUserId;
                $disciple->saveQuietly();

                $this->cascadePrimaryUserToDisciples($disciple->id, $primaryUserId, $depth + 1);
            }
        }
    }
}

