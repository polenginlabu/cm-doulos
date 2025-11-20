<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Forms\Components\UserSelect;
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

    // Rename from "My Disciples" to "My Network" in the sidebar and headings.
    protected static ?string $navigationLabel = 'My Network';

    protected static ?string $modelLabel = 'Network Member';

    protected static ?string $pluralModelLabel = 'My Network';

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
                            ->options(function () {
                                $authUser = Auth::user();

                                if ($authUser && $authUser->gender) {
                                    $label = ucfirst($authUser->gender);
                                    return [
                                        $authUser->gender => $label,
                                    ];
                                }

                                return [
                                    'male' => 'Male',
                                    'female' => 'Female',
                                ];
                            })
                            ->required()
                            ->default(function () {
                                return Auth::check() && Auth::user()->gender ? Auth::user()->gender : null;
                            }),
                        Forms\Components\Select::make('ministry_flag')
                            ->label('Focus Group')
                            ->options([
                                'youth' => 'Youth',
                                'professional' => 'Professional',
                                'j12' => 'J12',
                                'couples' => 'Couples',
                            ])
                            ->native(false)
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
                        UserSelect::cellLeader('cell_leader_id', [
                            'gender' => function ($get) {
                                return Auth::check() && Auth::user()->gender ? Auth::user()->gender : null;
                            },
                            'excludeCurrentUser' => false,
                            'activeOnly' => false, // Show all users (active and inactive)
                            'allowEmptySearch' => false, // Show all users when field is opened
                            'limit' => 100,
                        ])
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
                        Forms\Components\Hidden::make('primary_user_id')
                            ->default(function () {
                                // Always default to the authenticated user's network
                                if (!Auth::check()) {
                                    return null;
                                }

                                $authUser = Auth::user();

                                // If auth user is a primary leader, use their ID
                                if ($authUser->is_primary_leader) {
                                    return $authUser->id;
                                }

                                // Otherwise, use their network leader (primary_user_id)
                                return $authUser->primary_user_id;
                            })
                            ->dehydrated(),
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
                            ->helperText('Account must be activated to login')
                            ->visible(fn () => Auth::check() && Auth::user()->is_super_admin),
                        Forms\Components\Toggle::make('is_primary_leader')
                            ->label('Primary Leader')
                            ->default(false)
                            ->helperText('Mark if this user is a primary leader')
                            ->visible(fn () => Auth::check() && Auth::user()->is_super_admin),
                        Forms\Components\Toggle::make('is_super_admin')
                            ->label('Super Admin')
                            ->default(false)
                            ->helperText('Grant super admin privileges to this user')
                            ->visible(fn () => Auth::check() && Auth::user()->is_super_admin),
                        Forms\Components\Toggle::make('is_network_admin')
                            ->label('Network Admin')
                            ->default(false)
                            ->helperText('Can view and manage the entire network')
                            ->visible(fn () => Auth::check() && Auth::user()->is_super_admin),
                        Forms\Components\Toggle::make('is_equipping_admin')
                            ->label('Equipping Admin')
                            ->default(false)
                            ->helperText('Equipping admin privileges (for future use)')
                            ->visible(fn () => Auth::check() && Auth::user()->is_super_admin),
                    ])->columns(2)
                    ->visible(fn () => Auth::check() && Auth::user()->is_super_admin),

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

        // If user is logged in, only show their network and same gender (unless they're a super admin or network admin)
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            if ($user && $user->primary_user_id) {
                $query->where('id', '!=', $user->primary_user_id);
            }
            // Super admins and network admins can see all users

            if ($user && method_exists($user, 'getNetworkUserIds')) {
                $networkIds = $user->getNetworkUserIds();
                $query->whereIn('id', $networkIds);
            }

            // Filter by gender (same gender only)
            if ($user && $user->gender) {
                $query->where('gender', $user->gender);
            }

            // Exclude the authenticated user and their primary user (network leader)
            // so they don't appear in their own "My Network" list.
            $query->where('id', '!=', $user->id);

            if ($user && ($user->is_super_admin || $user->is_network_admin)) {
                return $query;
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
                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : '-')
                    ->colors([
                        'primary' => 'male',
                        'success' => 'female',
                    ]),
                Tables\Columns\BadgeColumn::make('ministry_flag')
                    ->label('Focus Group')
                    ->formatStateUsing(function (?string $state) {
                        return match ($state) {
                            'youth' => 'Youth',
                            'professional' => 'Professional',
                            'j12' => 'J12',
                            'couples' => 'Couples',
                            default => null,
                        };
                    })
                    ->colors([
                        'primary' => 'youth',
                        'info' => 'professional',
                        'warning' => 'j12',
                        'success' => 'couples',
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

