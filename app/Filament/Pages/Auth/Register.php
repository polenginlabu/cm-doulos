<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Models\Discipleship;
use App\Filament\Forms\Components\UserSelect;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Component;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getDataSourceInfoComponent(),
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

    protected function getDataSourceInfoComponent(): Component
    {
        return Placeholder::make('data_source_info')
            ->label('Data source')
            ->content(function (): string {
                $driver = DB::getDriverName();
                $database = (string) DB::connection()->getDatabaseName();

                if ($driver === 'sqlite') {
                    $basePath = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                    $database = str_starts_with($database, $basePath)
                        ? substr($database, strlen($basePath))
                        : $database;
                }

                $usersCount = 0;
                $networkLeadersCount = 0;

                if (Schema::hasTable('users')) {
                    $usersCount = User::count();
                    $networkLeadersCount = User::where('is_primary_leader', true)->count();
                }

                return "DB: {$driver} ({$database}); users: {$usersCount}; network leaders: {$networkLeadersCount}";
            })
            ->helperText('Use this to confirm the app is reading the expected database.');
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
                'primaryLeaderOnly' => true,
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
        $primaryLeaderId = $data['primary_user_id'] ?? null;
        $mentorId = $data['mentor_id'] ?? null;

        if ($primaryLeaderId) {
            $primaryLeader = User::find($primaryLeaderId);

            if (!$primaryLeader) {
                throw ValidationException::withMessages([
                    'primary_user_id' => 'Selected network leader does not exist.',
                ]);
            }

            if (!$primaryLeader->is_primary_leader) {
                throw ValidationException::withMessages([
                    'primary_user_id' => 'Selected network leader must be marked as a primary leader.',
                ]);
            }
        }

        if ($mentorId) {
            $mentor = User::find($mentorId);

            if (!$mentor) {
                throw ValidationException::withMessages([
                    'mentor_id' => 'Selected mentor does not exist.',
                ]);
            }

            if ($primaryLeaderId && $mentor->id !== (int) $primaryLeaderId && $mentor->primary_user_id !== (int) $primaryLeaderId) {
                throw ValidationException::withMessages([
                    'mentor_id' => 'Selected mentor is not under the selected network leader.',
                ]);
            }
        }

        if (!$mentorId && $primaryLeaderId) {
            $mentorId = $primaryLeaderId;
        }

        $user = DB::transaction(function () use ($data, $mentorId) {
            $user = parent::handleRegistration($data);

            if ($mentorId) {
                Discipleship::updateOrCreate(
                    [
                        'mentor_id' => $mentorId,
                        'disciple_id' => $user->id,
                    ],
                    [
                        'started_at' => now(),
                        'ended_at' => null,
                        'status' => 'active',
                    ]
                );
            }

            return $user;
        });

        return $user;
    }
}
