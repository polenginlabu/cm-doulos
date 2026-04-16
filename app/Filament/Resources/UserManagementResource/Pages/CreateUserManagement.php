<?php

namespace App\Filament\Resources\UserManagementResource\Pages;

use App\Filament\Resources\UserManagementResource;
use App\Models\Discipleship;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateUserManagement extends CreateRecord
{
    protected static string $resource = UserManagementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate email from first_name and last_name if email is not provided
        if (empty($data['email']) && (!empty($data['first_name']) || !empty($data['last_name']))) {
            $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

            if (!empty($fullName)) {
                $email = strtolower($fullName);
                $email = preg_replace('/\s+/', '.', trim($email));
                $email = preg_replace('/[^a-z0-9.]/', '', $email);
                $email = $email . '@gmail.com';

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
        if (isset($data['cell_leader_id']) && $data['cell_leader_id']) {
            $cellLeader = User::find($data['cell_leader_id']);
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

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $data = $this->data;

        // A disciple can ONLY have ONE active mentor at a time
        // cell_leader_id is the authoritative mentor; fall back to primary_user_id
        $mentorId = $data['cell_leader_id'] ?? $data['primary_user_id'] ?? null;

        if (!$mentorId) {
            return;
        }

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

                // Check if discipleship already exists
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
                ->body('Failed to create cell leader relationship: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

