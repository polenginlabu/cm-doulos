<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Discipleship;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

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
                while (\App\Models\User::where('email', $email)->exists()) {
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

        // Generate invitation token if email is provided
        if (isset($data['email']) && !empty($data['email'])) {
            $data['invitation_token'] = Str::random(64);
            $data['invited_at'] = now();
        }

        // Set invited_by to current user if not set
        if (!isset($data['invited_by']) && Auth::check()) {
            $data['invited_by'] = Auth::id();
        }

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

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $data = $this->data;

        // CRITICAL: A disciple can ONLY have ONE active mentor at a time
        // The cell_leader_id is the SINGLE SOURCE OF TRUTH

        // Determine the mentor (cell_leader_id takes priority)
        $mentorId = $data['cell_leader_id'] ?? null;

        // If no cell_leader_id but primary_user_id is set, use primary_user_id
        if (!$mentorId && isset($data['primary_user_id']) && $data['primary_user_id']) {
            $mentorId = $data['primary_user_id'];
        }

        // Fallback: If still no mentor and user is logged in, use current user
        if (!$mentorId && Auth::check() && Auth::id() != $user->id) {
            $mentorId = Auth::id();
        }

        // If we have a mentor, create the discipleship
        if ($mentorId) {
            // Prevent self-mentorship
            if ($mentorId === $user->id) {
                \Filament\Notifications\Notification::make()
                    ->title('Invalid Selection')
                    ->body('A user cannot be their own cell leader.')
                    ->danger()
                    ->send();
                return;
            }

            try {
                DB::transaction(function () use ($user, $mentorId) {
                    // CRITICAL: Deactivate ALL existing active discipleships for this disciple FIRST
                    // This ensures a disciple can only have ONE active mentor at a time
                    Discipleship::where('disciple_id', $user->id)
                        ->where('status', 'active')
                        ->update(['status' => 'inactive']);

                    // Check if discipleship already exists (shouldn't happen for new users, but just in case)
                    $existingDiscipleship = Discipleship::where('mentor_id', $mentorId)
                        ->where('disciple_id', $user->id)
                        ->lockForUpdate() // Lock the row to prevent race conditions
                        ->first();

                    if ($existingDiscipleship) {
                        // Reactivate if exists
                        $existingDiscipleship->update([
                            'status' => 'active',
                            'started_at' => $existingDiscipleship->started_at ?? now(),
                        ]);
                    } else {
                        // Create new discipleship
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

        // Update category based on engagement only if it wasn't manually set
        // Check if category was manually set in the form data
        $manualCategory = $this->data['category'] ?? null;
        if (!$manualCategory) {
            $user->updateCategory();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

