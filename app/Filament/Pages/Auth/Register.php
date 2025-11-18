<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Models\Discipleship;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Component;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Auth;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getFirstNameFormComponent(),
                        $this->getLastNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getContactFormComponent(),
                        $this->getGenderFormComponent(),
                        $this->getMentorFormComponent(),
                        $this->getNetworkLeaderFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getFirstNameFormComponent(): Component
    {
        return TextInput::make('first_name')
            ->label('First Name')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getLastNameFormComponent(): Component
    {
        return TextInput::make('last_name')
            ->label('Last Name')
            ->required()
            ->maxLength(255);
    }

    protected function getContactFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label('Contact')
            ->tel()
            ->maxLength(255);
    }

    protected function getGenderFormComponent(): Component
    {
        return Select::make('gender')
            ->label('Gender')
            ->options([
                'male' => 'Male',
                'female' => 'Female',
            ])
            ->nullable()
            ->reactive();
    }

    protected function getMentorFormComponent(): Component
    {
        return Select::make('mentor_id')
            ->label('Cell Leader')
            ->options(function ($get) {
                $query = User::orderBy('first_name')->orderBy('last_name');

                // Filter by selected gender (from registration form)
                $gender = $get('gender');
                if ($gender) {
                    $query->where('gender', $gender);
                }

                // Exclude logged-in user if editing
                if (Auth::check() && $this->record) {
                    $query->where('id', '!=', $this->record->id);
                }

                return $query->get()->mapWithKeys(function ($user) {
                    return [$user->id => $user->name];
                })->toArray();
            })
            ->getSearchResultsUsing(function (string $search, $get): array {
                $query = User::where('is_active', true)
                    ->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orderBy('first_name')->orderBy('last_name');

                // Filter by selected gender
                $gender = $get('gender');
                if ($gender) {
                    $query->where('gender', $gender);
                }

                // Exclude logged-in user if editing
                if (Auth::check() && $this->record) {
                    $query->where('id', '!=', $this->record->id);
                }

                return $query->limit(50)->get()->mapWithKeys(function ($user) {
                    return [$user->id => $user->name];
                })->toArray();
            })
            ->searchable()
            ->nullable()
            ->helperText('Select your mentor (cell leader). Only users with the same gender as you are shown. A disciple can only have one active mentor.')
            ->reactive();
    }

    protected function getNetworkLeaderFormComponent(): Component
    {
        return Select::make('primary_user_id')
            ->label('Network Leader')
            ->options(function () {
                $query = User::where('is_active', true)
                    ->where('is_primary_leader', true)
                    ->orderBy('first_name')->orderBy('last_name');

                // Exclude logged-in user if editing
                if (Auth::check() && $this->record) {
                    $query->where('id', '!=', $this->record->id);
                }

                return $query->get()
                    ->mapWithKeys(function ($user) {
                        return [$user->id => $user->name];
                    })
                    ->toArray();
            })
            ->getSearchResultsUsing(function (string $search): array {
                $query = User::where('is_active', true)
                    ->where('is_primary_leader', true)
                    ->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orderBy('first_name')->orderBy('last_name');

                // Exclude logged-in user if editing
                if (Auth::check() && $this->record) {
                    $query->where('id', '!=', $this->record->id);
                }

                return $query->limit(50)
                    ->get()
                    ->mapWithKeys(function ($user) {
                        return [$user->id => $user->name];
                    })
                    ->toArray();
            })
            ->searchable()
            ->preload()
            ->nullable();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set is_active to false - user needs admin activation
        $data['is_active'] = false;

        return $data;
    }

    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = parent::handleRegistration($data);

        // If mentor is set, create discipleship relationship
        if ($data['mentor_id'] ?? null) {
            Discipleship::create([
                'mentor_id' => $data['mentor_id'],
                'disciple_id' => $user->id,
                'started_at' => now(),
                'status' => 'active',
            ]);
        }

        // If network leader is set, create discipleship relationship (network leader as mentor)
        // Note: This will fail if mentor_id is also set, due to one-to-one constraint
        if ($data['primary_user_id'] ?? null && !($data['mentor_id'] ?? null)) {
            Discipleship::create([
                'mentor_id' => $data['primary_user_id'],
                'disciple_id' => $user->id,
                'started_at' => now(),
                'status' => 'active',
            ]);
        }

        return $user;
    }
}

