<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserManagementResource\Pages;
use App\Filament\Forms\Components\UserSelect;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserManagementResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'User Management';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?string $navigationGroup = 'Network Management';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        // Only allow access if user is super admin or network admin
        return Auth::check() && (Auth::user()->is_super_admin || Auth::user()->is_network_admin);
    }

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
                        Forms\Components\DatePicker::make('date_of_birth'),
                    ])->columns(2),

                Forms\Components\Section::make('Church Information')
                    ->schema([
                        Forms\Components\Select::make('cell_group_id')
                            ->label('Cell Group')
                            ->relationship('cellGroup', 'name')
                            ->searchable()
                            ->preload(),
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
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // If cell leader is a primary leader, automatically set as primary user
                                if ($state) {
                                    $cellLeader = \App\Models\User::find($state);
                                    if ($cellLeader && $cellLeader->is_primary_leader) {
                                        $set('primary_user_id', $state);
                                    }
                                }
                            })
                            ->helperText('Select the cell leader (mentor) for this member. Only users with the same gender as you are shown. If the cell leader is a primary leader, they will automatically be set as the primary user.')
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record) {
                                    $discipleship = $record->mentor;
                                    if ($discipleship && $discipleship->mentor) {
                                        $component->state($discipleship->mentor->id);
                                    }
                                }
                            }),
                        UserSelect::networkLeader('primary_user_id', [
                            'excludeCurrentUser' => false,
                            'activeOnly' => false, // Show all users (active and inactive)
                            'allowEmptySearch' => false, // Show all users when field is opened
                            'limit' => 100,
                        ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                // If primary user is set, automatically set as cell leader
                                if ($state) {
                                    $set('cell_leader_id', $state);
                                }
                            })
                            ->helperText('Select the primary user (primary leader) for this member. They will automatically be set as the cell leader. All disciples will inherit this primary user.'),
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

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Super admins can see all users
        return parent::getEloquentQuery();
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
                    ->label('Primary User')
                    ->sortable()
                    ->placeholder('No primary user'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Active Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
                Tables\Filters\TernaryFilter::make('is_primary_leader')
                    ->label('Primary Leader'),
                Tables\Filters\TernaryFilter::make('is_super_admin')
                    ->label('Super Admin'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserManagements::route('/'),
            'create' => Pages\CreateUserManagement::route('/create'),
            'view' => Pages\ViewUserManagement::route('/{record}'),
            'edit' => Pages\EditUserManagement::route('/{record}/edit'),
        ];
    }
}

