<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Models\Discipleship;
use App\Filament\Forms\Components\UserSelect;
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
                        $this->getNetworkLeaderFormComponent(),
                        $this->getMentorFormComponent(),
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
            ->required()
            ->reactive();
    }

    protected function getMentorFormComponent(): Component
    {
        return UserSelect::cellLeader('mentor_id', [
            'gender' => function ($get) {
                return $get('gender');
            },
            'networkLeaderId' => function ($get) {
                return $get('primary_user_id');
            },
            'excludeCurrentUser' => false,
            'activeOnly' => false, // Show all users (active and inactive)
            'allowEmptySearch' => false, // Show all users when field is opened
            'limit' => 100,
            // 'excludeRecord' => function ($get) {
            //     return Auth::check() && $this->record ? $this->record->id : null;
            // },
        ])
            ->reactive()
            ->disabled(function ($get) {
                // Disable cell leader if no network leader is selected
                return !$get('primary_user_id');
            })
            ->helperText('Select your mentor (cell leader). Only cell leaders from the selected network leader are shown. You must select a network leader first.');
    }

    protected function getNetworkLeaderFormComponent(): Component
    {
        return UserSelect::make('primary_user_id', [
                'label' => 'Network Leader',
                'primaryLeaderOnly' => false,
                'gender' => null, // No gender filter
                'excludeCurrentUser' => false,
                'activeOnly' => false, // Show all users (active and inactive)
                'allowEmptySearch' => false, // Show all users when field is opened
                'limit' => 100,
            ])
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($state, $set) {
                // Clear cell leader when network leader changes
                $set('mentor_id', null);
            });
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

