<?php

namespace App\Filament\Resources\UserManagementResource\Pages;

use App\Filament\Resources\UserManagementResource;
use App\Models\Discipleship;
use Filament\Resources\Pages\CreateRecord;

class CreateUserManagement extends CreateRecord
{
    protected static string $resource = UserManagementResource::class;

    protected function afterCreate(): void
    {
        $user = $this->record;
        $data = $this->data;

        // Helper function to create discipleship relationship
        $createDiscipleship = function ($mentorId) use ($user) {
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
            Discipleship::where('disciple_id', $user->id)
                ->where('status', 'active')
                ->where('mentor_id', '!=', $mentorId)
                ->update(['status' => 'inactive']);

            // Check if discipleship already exists
            $existingDiscipleship = Discipleship::where('mentor_id', $mentorId)
                ->where('disciple_id', $user->id)
                ->first();

            try {
                if (!$existingDiscipleship) {
                    Discipleship::create([
                        'mentor_id' => $mentorId,
                        'disciple_id' => $user->id,
                        'started_at' => now(),
                        'status' => 'active',
                    ]);
                } else {
                    // Reactivate if exists
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

        // If cell_leader_id is set, create discipleship relationship (cell leader as mentor)
        if (isset($data['cell_leader_id']) && $data['cell_leader_id']) {
            $createDiscipleship($data['cell_leader_id']);
        }

        // If primary_user_id is set, create discipleship relationship (primary user as mentor)
        if (isset($data['primary_user_id']) && $data['primary_user_id']) {
            $createDiscipleship($data['primary_user_id']);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

