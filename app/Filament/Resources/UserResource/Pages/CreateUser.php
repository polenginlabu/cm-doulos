<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\Discipleship;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate invitation token if email is provided
        if (isset($data['email']) && !empty($data['email'])) {
            $data['invitation_token'] = Str::random(64);
            $data['invited_at'] = now();
        }

        // Set invited_by to current user if not set
        if (!isset($data['invited_by']) && Auth::check()) {
            $data['invited_by'] = Auth::id();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Automatically create discipleship relationship if current user is logged in
        if (Auth::check() && Auth::id() != $this->record->id) {
            Discipleship::create([
                'mentor_id' => Auth::id(),
                'disciple_id' => $this->record->id,
                'started_at' => now(),
                'status' => 'active',
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

