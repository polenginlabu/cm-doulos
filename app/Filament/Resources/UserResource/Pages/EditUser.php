<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Discipleship;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    // Custom heading for edit page instead of the default "Edit Network Member".
    protected static ?string $title = 'Edit Disciple';

    protected ?int $newPrimaryUserId = null;
    public ?int $cellLeaderId = null;
    protected bool $manualCategorySet = false;

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

        // Check if category was manually changed by comparing with original value
        $originalCategory = $this->record->category;
        $newCategory = $data['category'] ?? null;
        // If category is explicitly set and different from original, user manually changed it
        $this->manualCategorySet = isset($data['category']) && $newCategory !== $originalCategory;

        // Get cell_leader_id from Livewire property (set by afterStateUpdated)
        // This is the SINGLE SOURCE OF TRUTH for the mentor
        $cellLeaderId = $this->cellLeaderId;

        if (!$cellLeaderId) {
            $formState = $this->form->getState();
            $cellLeaderId = $formState['cell_leader_id'] ?? null;
        }

        if (!$cellLeaderId) {
            $cellLeaderComponent = $this->form->getComponent('cell_leader_id');
            if ($cellLeaderComponent) {
                $cellLeaderId = $cellLeaderComponent->getState();
            }
        }

        // Store cell_leader_id for use in afterSave
        $this->cellLeaderId = $cellLeaderId;

        // Always set primary_user_id to authenticated user's network
        if (Auth::check()) {
            $authUser = Auth::user();
            
            // If auth user is a primary leader, use their ID
            if ($authUser->is_primary_leader) {
                $data['primary_user_id'] = $authUser->id;
            }
            // Otherwise, use their network leader (primary_user_id)
            elseif ($authUser->primary_user_id) {
                $data['primary_user_id'] = $authUser->primary_user_id;
            }
            
            // Always set gender to authenticated user's gender
            if ($authUser->gender) {
                $data['gender'] = $authUser->gender;
            }
        }

        // Auto-link cell leader and network leader
        // If cell leader is a primary leader, set as network leader (but only if in same network)
        if ($cellLeaderId) {
            $cellLeader = \App\Models\User::find($cellLeaderId);
            if ($cellLeader) {
                // If cell leader is a primary leader and matches auth user's network, set as network leader
                if ($cellLeader->is_primary_leader && isset($data['primary_user_id']) && $cellLeader->id == $data['primary_user_id']) {
                    // Already set correctly
                }
                // Otherwise, inherit primary_user_id from cell leader (but only if it matches auth user's network)
                elseif ($cellLeader->primary_user_id && isset($data['primary_user_id']) && $cellLeader->primary_user_id == $data['primary_user_id']) {
                    // Keep the auth user's network
                }
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

        // Get cell_leader_id from Livewire property - SINGLE SOURCE OF TRUTH
        $cellLeaderId = $this->cellLeaderId;

        if (!$cellLeaderId) {
            $formState = $this->form->getState();
            $cellLeaderId = $formState['cell_leader_id'] ?? null;
        }

        if (!$cellLeaderId) {
            $cellLeaderComponent = $this->form->getComponent('cell_leader_id');
            if ($cellLeaderComponent) {
                $cellLeaderId = $cellLeaderComponent->getState();
            }
        }

        // Cascade primary_user_id change to all disciples
        if (isset($this->newPrimaryUserId) && $this->newPrimaryUserId != $user->getOriginal('primary_user_id')) {
            $this->cascadePrimaryUserToDisciples($user->id, $this->newPrimaryUserId);
        }

        // CRITICAL: Handle discipleship relationship
        // A disciple can ONLY have ONE active mentor at a time
        // Use database transaction to ensure atomicity

        try {
            DB::transaction(function () use ($user, $cellLeaderId) {
                // Step 1: Deactivate ALL existing active discipleships for this disciple FIRST
                // This must happen before creating/activating a new one
                Discipleship::where('disciple_id', $user->id)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);

                // Step 2: If cell_leader_id is set, create/activate ONE discipleship
                if ($cellLeaderId) {
                    // Prevent self-mentorship
                    if ($cellLeaderId === $user->id) {
                        throw new \Exception('A user cannot be their own cell leader.');
                    }

                    // Check if discipleship already exists (regardless of status)
                    $existingDiscipleship = Discipleship::where('mentor_id', $cellLeaderId)
                        ->where('disciple_id', $user->id)
                        ->lockForUpdate() // Lock the row to prevent race conditions
                        ->first();

                    if ($existingDiscipleship) {
                        // Reactivate existing discipleship
                        $existingDiscipleship->update([
                            'status' => 'active',
                            'started_at' => $existingDiscipleship->started_at ?? now(),
                        ]);
                    } else {
                        // Create new discipleship
                        Discipleship::create([
                            'mentor_id' => $cellLeaderId,
                            'disciple_id' => $user->id,
                            'started_at' => now(),
                            'status' => 'active',
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return;
        }

        // Update category based on engagement only if it wasn't manually set
        if (!$this->manualCategorySet) {
            $user->updateCategory();
        }

        \Filament\Notifications\Notification::make()
            ->title('Success')
            ->body('User updated successfully.')
            ->success()
            ->send();
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

