<?php

namespace App\Filament\Resources\UserManagementResource\Pages;

use App\Filament\Resources\UserManagementResource;
use App\Models\Discipleship;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CreateUserManagement extends CreateRecord
{
    protected static string $resource = UserManagementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate email from first_name and last_name if email is not provided
        if (empty($data['email']) && (!empty($data['first_name']) || !empty($data['last_name']))) {
            // Combine first_name and last_name
            $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

            if (!empty($fullName)) {
                // Convert name to email format: "john pilip" -> "john.pilip@gmail.com"
                $email = strtolower($fullName);
                $email = preg_replace('/\s+/', '.', trim($email)); // Replace spaces with dots
                $email = preg_replace('/[^a-z0-9.]/', '', $email); // Remove special characters
                $email = $email . '@gmail.com';

                // Check if email already exists, if so, append a number
                $originalEmail = $email;
                $counter = 1;
                while (User::where('email', $email)->exists()) {
                    $email = str_replace('@gmail.com', $counter . '@gmail.com', $originalEmail);
                    $counter++;
                }

                $data['email'] = $email;
            }
        }

        // Set default password if not provided
        if (empty($data['password'])) {
            $data['password'] = Hash::make('P@ssWord1');
        }

        // Auto-link cell leader and primary user
        // If cell leader is a primary leader, set as primary user
        if (isset($data['cell_leader_id']) && $data['cell_leader_id']) {
            $cellLeader = User::find($data['cell_leader_id']);
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

        return $data;
    }

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

