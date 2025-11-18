<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'My Disciples';

    protected static ?string $modelLabel = 'Disciple';

    protected static ?string $pluralModelLabel = 'My Disciples';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('First Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Optional. Auto-generated from first name and last name before saving if left blank (e.g., "john pilip" → "john.pilip@gmail.com")'),
                        Forms\Components\TextInput::make('phone')
                            ->label('Contact')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ])
                            ->nullable(),
                        Forms\Components\DatePicker::make('date_of_birth'),
                    ])->columns(2),

                Forms\Components\Section::make('Church Information')
                    ->schema([
                        Forms\Components\Select::make('cell_group_id')
                            ->label('Cell Group')
                            ->relationship('cellGroup', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $record) {
                                // Auto-update category when cell group changes
                                if ($record) {
                                    $record->updateCategory();
                                    $set('category', $record->category);
                                }
                            }),
                        Forms\Components\Select::make('cell_leader_id')
                            ->label('Cell Leader')
                            ->options(function ($record) {
                                $query = \App\Models\User::orderBy('first_name')->orderBy('last_name');

                                // Filter by logged-in user's gender
                                if (Auth::check() && Auth::user()->gender) {
                                    $query->where('gender', Auth::user()->gender);
                                }

                                return $query->get()->mapWithKeys(function ($user) {
                                    return [$user->id => $user->name];
                                })->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search, $record): array {
                                $query = \App\Models\User::where(function ($q) use ($search) {
                                    $q->where('first_name', 'like', "%{$search}%")
                                      ->orWhere('last_name', 'like', "%{$search}%");
                                })
                                    ->orderBy('first_name')->orderBy('last_name');

                                // Filter by logged-in user's gender
                                if (Auth::check() && Auth::user()->gender) {
                                    $query->where('gender', Auth::user()->gender);
                                }

                                return $query->limit(50)->get()->mapWithKeys(function ($user) {
                                    return [$user->id => $user->name];
                                })->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string =>
                                \App\Models\User::find($value)?->name
                            )
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get, $livewire) {
                                // Store cell_leader_id in Livewire property for use in afterSave
                                if (property_exists($livewire, 'cellLeaderId')) {
                                    $livewire->cellLeaderId = $state;
                                }

                                // If cell leader is selected, set network leader accordingly
                                if ($state) {
                                    $cellLeader = \App\Models\User::find($state);
                                    if ($cellLeader) {
                                        if ($cellLeader->is_primary_leader) {
                                            // If cell leader is a primary leader, set as network leader
                                            $set('primary_user_id', $state);
                                        } elseif ($cellLeader->primary_user_id) {
                                            // If cell leader has a network leader, inherit it
                                            $set('primary_user_id', $cellLeader->primary_user_id);
                                        }
                                    }
                                }
                            })
                            ->helperText('Select the cell leader (mentor) for this disciple. Only users with the same gender as you are shown. If the cell leader is a primary leader, they will automatically be set as the network leader. If the cell leader has a network leader, it will be inherited.')
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, $record, $livewire) {
                                if ($record) {
                                    // Get the active discipleship for this user (where user is the disciple)
                                    $discipleship = \App\Models\Discipleship::where('disciple_id', $record->id)
                                        ->where('status', 'active')
                                        ->with('mentor')
                                        ->first();

                                    if ($discipleship && $discipleship->mentor) {
                                        $mentorId = $discipleship->mentor->id;
                                        $component->state($mentorId);
                                        // Also set the Livewire property
                                        if (property_exists($livewire, 'cellLeaderId')) {
                                            $livewire->cellLeaderId = $mentorId;
                                        }
                                    } else {
                                        // No active discipleship, clear the field
                                        $component->state(null);
                                        if (property_exists($livewire, 'cellLeaderId')) {
                                            $livewire->cellLeaderId = null;
                                        }
                                    }
                                }
                            }),
                        Forms\Components\Select::make('primary_user_id')
                            ->label('Network Leader')
                            ->options(function ($record, $get) {
                                // If cell leader is selected and has a network leader, use that
                                $cellLeaderId = $get('cell_leader_id');
                                if ($cellLeaderId) {
                                    $cellLeader = \App\Models\User::find($cellLeaderId);
                                    if ($cellLeader && $cellLeader->primary_user_id && !$cellLeader->is_primary_leader) {
                                        // Cell leader has a network leader, return only that option
                                        $networkLeader = \App\Models\User::find($cellLeader->primary_user_id);
                                        if ($networkLeader) {
                                            return [$networkLeader->id => $networkLeader->name];
                                        }
                                    }
                                }

                                $query = \App\Models\User::where('is_primary_leader', true)
                                    ->orderBy('first_name')->orderBy('last_name');

                                // Exclude logged-in user
                                if (Auth::check()) {
                                    $query->where('id', '!=', Auth::id());
                                }
                                // Exclude the user being edited
                                if ($record) {
                                    $query->where('id', '!=', $record->id);
                                }

                                return $query->get()->mapWithKeys(function ($user) {
                                    return [$user->id => $user->name];
                                })->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search, $record, $get): array {
                                // If cell leader is selected and has a network leader, use that
                                $cellLeaderId = $get('cell_leader_id');
                                if ($cellLeaderId) {
                                    $cellLeader = \App\Models\User::find($cellLeaderId);
                                    if ($cellLeader && $cellLeader->primary_user_id && !$cellLeader->is_primary_leader) {
                                        // Cell leader has a network leader, return only that option
                                        $networkLeader = \App\Models\User::find($cellLeader->primary_user_id);
                                        if ($networkLeader) {
                                            return [$networkLeader->id => $networkLeader->name];
                                        }
                                    }
                                }

                                $query = \App\Models\User::where('is_primary_leader', true)
                                    ->where(function ($q) use ($search) {
                                        $q->where('first_name', 'like', "%{$search}%")
                                          ->orWhere('last_name', 'like', "%{$search}%");
                                    })
                                    ->orderBy('first_name')->orderBy('last_name');

                                // Exclude logged-in user
                                if (Auth::check()) {
                                    $query->where('id', '!=', Auth::id());
                                }
                                // Exclude the user being edited
                                if ($record) {
                                    $query->where('id', '!=', $record->id);
                                }

                                return $query->limit(50)->get()->mapWithKeys(function ($user) {
                                    return [$user->id => $user->name];
                                })->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string =>
                                \App\Models\User::find($value)?->name
                            )
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->disabled(function ($get) {
                                // Disable if cell leader is selected and has a network leader
                                $cellLeaderId = $get('cell_leader_id');
                                if ($cellLeaderId) {
                                    $cellLeader = \App\Models\User::find($cellLeaderId);
                                    if ($cellLeader && $cellLeader->primary_user_id && !$cellLeader->is_primary_leader) {
                                        return true; // Disable because cell leader has a network leader
                                    }
                                }
                                return false;
                            })
                            ->afterStateUpdated(function ($state, $set) {
                                // If network leader is set, automatically set as cell leader
                                if ($state) {
                                    $set('cell_leader_id', $state);
                                }
                            })
                            ->helperText(function ($get) {
                                $cellLeaderId = $get('cell_leader_id');
                                if ($cellLeaderId) {
                                    $cellLeader = \App\Models\User::find($cellLeaderId);
                                    if ($cellLeader && $cellLeader->primary_user_id && !$cellLeader->is_primary_leader) {
                                        return 'Network leader is automatically inherited from the selected cell leader.';
                                    }
                                }
                                return 'Select the network leader (primary leader) for this disciple. They will automatically be set as the cell leader. All disciples will inherit this network leader.';
                            }),
                        Forms\Components\Select::make('attendance_status')
                            ->options([
                                '1st' => '1st Time',
                                '2nd' => '2nd Time',
                                '3rd' => '3rd Time',
                                '4th' => '4th Time',
                                'regular' => 'Regular',
                            ])
                            ->default('1st')
                            ->required(),
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options([
                                'C1' => 'C1 - Engaged in all 4 (Sunday Service, Cell Group, Devotion, Training)',
                                'C2' => 'C2 - Engaged in 3 activities',
                                'C3' => 'C3 - Engaged in 2 activities',
                                'C4' => 'C4 - Engaged in 1 or fewer activities',
                            ])
                            ->helperText('Category is automatically calculated based on engagement. You can manually override it here.'),
                        Forms\Components\DatePicker::make('first_attendance_date')
                            ->label('First Attendance Date'),
                        Forms\Components\DatePicker::make('last_attendance_date')
                            ->label('Last Attendance Date'),
                        Forms\Components\TextInput::make('total_attendances')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(3),

                Forms\Components\Section::make('Account Settings')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText('Leave blank to use default password "P@ssWord1" (or keep current password when editing)'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Account Active')
                            ->default(false)
                            ->helperText('Account must be activated to login'),
                        Forms\Components\Toggle::make('is_primary_leader')
                            ->label('Primary Leader')
                            ->default(false)
                            ->helperText('Mark if this user is a primary leader'),
                        Forms\Components\Toggle::make('is_super_admin')
                            ->label('Super Admin')
                            ->default(false)
                            ->helperText('Grant super admin privileges to this user'),
                        Forms\Components\Toggle::make('is_network_admin')
                            ->label('Network Admin')
                            ->default(false)
                            ->helperText('Can view and manage the entire network'),
                        Forms\Components\Toggle::make('is_equipping_admin')
                            ->label('Equipping Admin')
                            ->default(false)
                            ->helperText('Equipping admin privileges (for future use)'),
                    ])->columns(2),

                Forms\Components\Section::make('Invitation')
                    ->schema([
                        Forms\Components\Placeholder::make('auto_assigned')
                            ->label('Network Assignment')
                            ->content('This disciple will automatically be added to your network.')
                            ->visible(fn (string $context): bool => $context === 'create' && Auth::check()),
                    ])
                    ->visible(fn (string $context): bool => $context === 'create'),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // If user is logged in, only show their network (unless they're a super admin or network admin)
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            // Super admins and network admins can see all users
            if ($user && ($user->is_super_admin || $user->is_network_admin)) {
                return $query;
            }
            if ($user && method_exists($user, 'getNetworkUserIds')) {
                $networkIds = $user->getNetworkUserIds();
                $query->whereIn('id', $networkIds);
            }
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->getStateUsing(fn ($record) => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(false),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No email'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('No phone'),
                Tables\Columns\TextColumn::make('cellGroup.name')
                    ->label('Cell Group')
                    ->sortable()
                    ->placeholder('No cell group'),
                Tables\Columns\BadgeColumn::make('attendance_status')
                    ->colors([
                        'warning' => '1st',
                        'info' => '2nd',
                        'success' => '3rd',
                        'primary' => '4th',
                        'gray' => 'regular',
                    ]),
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Category')
                    ->colors([
                        'success' => 'C1',
                        'primary' => 'C2',
                        'warning' => 'C3',
                        'danger' => 'C4',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_attendances')
                    ->label('Total Attendances')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_primary_leader')
                    ->label('Primary Leader')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_super_admin')
                    ->label('Super Admin')
                    ->boolean(),
                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : '-')
                    ->colors([
                        'primary' => 'male',
                        'success' => 'female',
                    ]),
                Tables\Columns\TextColumn::make('primaryUser.name')
                    ->label('Network Leader')
                    ->sortable()
                    ->placeholder('No network leader'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'C1' => 'C1 - Engaged in all 4',
                        'C2' => 'C2 - Engaged in 3',
                        'C3' => 'C3 - Engaged in 2',
                        'C4' => 'C4 - Engaged in 1 or fewer',
                    ]),
                Tables\Filters\SelectFilter::make('attendance_status')
                    ->options([
                        '1st' => '1st Time',
                        '2nd' => '2nd Time',
                        '3rd' => '3rd Time',
                        '4th' => '4th Time',
                        'regular' => 'Regular',
                    ]),
                Tables\Filters\SelectFilter::make('cell_group_id')
                    ->label('Cell Group')
                    ->relationship('cellGroup', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DisciplesRelationManager::class,
            RelationManagers\AttendancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

