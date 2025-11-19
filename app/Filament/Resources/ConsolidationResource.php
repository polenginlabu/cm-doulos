<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsolidationResource\Pages;
use App\Filament\Forms\Components\UserSelect;
use App\Models\ConsolidationMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsolidationResource extends Resource
{
    protected static ?string $model = ConsolidationMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Consolidation';

    protected static ?string $modelLabel = 'Consolidation Member';

    protected static ?string $pluralModelLabel = 'Consolidation Members';

    protected static ?int $navigationSort = 9;

    /**
     * Get filtered user IDs based on network and gender.
     */
    protected static function getFilteredUserIds(): array
    {
        if (!Auth::check()) {
            return [];
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Super admins can see all users
        if ($user->is_super_admin) {
            $query = \App\Models\User::query();
        } elseif ($user->is_network_admin) {
            // Network admins can see all users (no network restriction)
            $query = \App\Models\User::query();
        } else {
            // Regular users can only see their network
            if (!method_exists($user, 'getNetworkUserIds')) {
                return [$user->id];
            }
            $networkIds = $user->getNetworkUserIds();
            $query = \App\Models\User::whereIn('id', $networkIds);
        }

        // Filter by gender (same gender only) - except for super admins
        if (!$user->is_super_admin && $user->gender) {
            $query->where('gender', $user->gender);
        }

        return $query->pluck('id')->toArray();
    }

    public static function getEloquentQuery(): Builder
    {
        // Get users with 1st-4th timer status (exclude regular)
        // Include users that either have a consolidation record OR are 1st-4th timers
        $query = \App\Models\User::query()
            ->whereIn('users.attendance_status', ['1st', '2nd', '3rd', '4th'])
            ->leftJoin('consolidation_members', 'users.id', '=', 'consolidation_members.user_id')
            ->leftJoin('users as consolidators', 'consolidation_members.consolidator_id', '=', 'consolidators.id')
            ->select([
                'users.*',
                DB::raw("COALESCE(consolidation_members.status, 'not_contacted') as consolidation_status"),
                'consolidation_members.consolidator_id',
                DB::raw("CONCAT(consolidators.first_name, ' ', consolidators.last_name) as consolidator_name"),
                DB::raw("COALESCE(consolidation_members.added_at, users.first_attendance_date, users.created_at) as date"),
                'consolidation_members.interest',
                'consolidation_members.next_action',
                DB::raw("CASE WHEN consolidation_members.id IS NOT NULL THEN 'consolidation_member' ELSE 'user' END as source_type"),
                'consolidation_members.id as consolidation_id',
            ])
            ->distinct();

        // Apply network and gender filtering
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Exclude the current user from the consolidation table
            $query->where('users.id', '!=', $user->id);

            if (!$user->is_super_admin && !$user->is_network_admin) {
                // Regular users: Filter to show only users in their network
                if (method_exists($user, 'getNetworkUserIds')) {
                    $networkIds = $user->getNetworkUserIds();
                    $query->whereIn('users.id', $networkIds);
                }
            }
            // Network admins: Can see all network members but still gender-specific
            // Super admins: Can see everything (no gender filter)

            // Apply gender filtering for all non-super-admin users
            if (!$user->is_super_admin && $user->gender) {
                $query->where('users.gender', $user->gender);
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Member Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Member')
                            ->options(function () {
                                $filteredUserIds = static::getFilteredUserIds();
                                if (empty($filteredUserIds)) {
                                    return [];
                                }
                                return \App\Models\User::whereIn('id', $filteredUserIds)
                                    ->whereIn('attendance_status', ['1st', '2nd', '3rd', '4th'])
                                    ->orderBy('first_name')
                                    ->orderBy('last_name')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        return [$user->id => $user->name . ' (' . $user->attendance_status . ')'];
                                    })
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                $filteredUserIds = static::getFilteredUserIds();
                                if (empty($filteredUserIds)) {
                                    return [];
                                }
                                return \App\Models\User::whereIn('id', $filteredUserIds)
                                    ->whereIn('attendance_status', ['1st', '2nd', '3rd', '4th'])
                                    ->where(function ($q) use ($search) {
                                        $q->where('first_name', 'like', "%{$search}%")
                                          ->orWhere('last_name', 'like', "%{$search}%");
                                    })
                                    ->orderBy('first_name')
                                    ->orderBy('last_name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        return [$user->id => $user->name . ' (' . $user->attendance_status . ')'];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\User::find($value)?->name)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($record) => $record !== null) // Disable editing user_id after creation
                            ->helperText('Select a member with 1st-4th timer status'),
                        Forms\Components\Select::make('attendance_status')
                            ->label('Attendance Status')
                            ->options([
                                '1st' => '1st Timer',
                                '2nd' => '2nd Timer',
                                '3rd' => '3rd Timer',
                                '4th' => '4th Timer',
                                'regular' => 'Regular (No consolidation needed)',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // If changed to regular, mark consolidation as completed
                                if ($state === 'regular') {
                                    $set('status', 'completed');
                                }
                            })
                            ->dehydrated(false)
                            ->helperText('Update the member\'s attendance status. If set to Regular, consolidation will be marked as completed.'),
                        Forms\Components\DatePicker::make('added_at')
                            ->label('Added Date')
                            ->default(now())
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Consolidation Details')
                    ->schema([
                        UserSelect::make('consolidator_id', [
                            'label' => 'Consolidator',
                            // Use the same network + gender filtering as other user selects.
                            'excludeCurrentUser' => false,
                            'activeOnly' => false,
                            'allowEmptySearch' => false,
                            'limit' => 100,
                        ])
                            ->required()
                            ->helperText('The person responsible for consolidating this member'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'not_contacted' => 'Not Contacted',
                                'contacted' => 'Contacted',
                                'in_progress' => 'In Progress',
                                'follow_up_scheduled' => 'Follow-up Scheduled',
                                'completed' => 'Completed',
                            ])
                            ->default('not_contacted')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if (in_array($state, ['contacted', 'in_progress', 'follow_up_scheduled', 'completed']) && !$get('contacted_at')) {
                                    $set('contacted_at', now());
                                }
                                if ($state === 'completed') {
                                    $set('completed_at', now());
                                    // When consolidation is completed, set attendance status to regular
                                    $set('attendance_status', 'regular');
                                }
                            }),
                        Forms\Components\DatePicker::make('contacted_at')
                            ->label('Contacted Date')
                            ->visible(fn ($get) => in_array($get('status'), ['contacted', 'in_progress', 'follow_up_scheduled', 'completed'])),
                        Forms\Components\DatePicker::make('completed_at')
                            ->label('Completed Date')
                            ->visible(fn ($get) => $get('status') === 'completed'),
                    ])->columns(2),
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('interest')
                            ->label('Interest')
                            ->rows(3)
                            ->helperText('What the member is interested in'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->helperText('Additional notes about this member'),
                        Forms\Components\Textarea::make('next_action')
                            ->label('Next Action')
                            ->rows(2)
                            ->helperText('Next action needed (e.g., "Schedule coffee meeting", "Send small group info", "Home visit", "Call", "Text", "Message on Facebook")'),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(function ($record) {
                // Make the whole row clickable, using the SAME logic as the Edit action.
                // The EditConsolidation page expects the `record` parameter to be the USER ID,
                // not the consolidation_members.id. It will create the consolidation record if needed.
                $id = is_object($record) ? ($record->id ?? null) : ($record['id'] ?? null);

                if ($id && !str_starts_with((string) $id, 'user_') && $id !== '0' && $id !== 0) {
                    return static::getUrl('edit', ['record' => $id]);
                }

                // If we don't have a valid numeric ID, don't make the row clickable.
                return null;
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(function ($record) {
                        // Use the name accessor or get from attributes
                        if (is_object($record)) {
                            // If it's a User model, use the name accessor
                            if ($record instanceof \App\Models\User) {
                                return $record->name;
                            }
                            // Otherwise try to get from attributes
                            return $record->name ?? ($record->first_name ?? '') . ' ' . ($record->last_name ?? '');
                        }
                        // For array access
                        return $record['name'] ?? (($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? ''));
                    })
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('Attendance Status')
                    ->badge()
                    ->colors([
                        'info' => '1st',
                        'primary' => '2nd',
                        'warning' => '3rd',
                        'danger' => '4th',
                        'success' => 'regular',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('consolidation_status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray' => 'not_contacted',
                        'info' => 'contacted',
                        'warning' => 'in_progress',
                        'purple' => 'follow_up_scheduled',
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'not_contacted' => 'Not Contacted',
                        'contacted' => 'Contacted',
                        'in_progress' => 'In Progress',
                        'follow_up_scheduled' => 'Follow-up Scheduled',
                        'completed' => 'Completed',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('consolidator_name')
                    ->label('Consolidator')
                    ->getStateUsing(function ($record) {
                        // Get consolidator_name from the record (loaded from join)
                        if (is_object($record)) {
                            return $record->consolidator_name ?? 'Unassigned';
                        }
                        return $record['consolidator_name'] ?? 'Unassigned';
                    })
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Added Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_action')
                    ->label('Next Action')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('source_type')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'user' ? 'Member' : 'Consolidation')
                    ->colors([
                        'info' => 'user',
                        'gray' => 'consolidation_member',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('consolidation_status')
                    ->label('Consolidation Status')
                    ->options([
                        'not_contacted' => 'Not Contacted',
                        'contacted' => 'Contacted',
                        'in_progress' => 'In Progress',
                        'follow_up_scheduled' => 'Follow-up Scheduled',
                        'completed' => 'Completed',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['value'])) {
                            return $query->where('consolidation_members.status', $data['value']);
                        }
                        return $query;
                    }),
                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('Attendance Status')
                    ->options([
                        '1st' => '1st Timer',
                        '2nd' => '2nd Timer',
                        '3rd' => '3rd Timer',
                        '4th' => '4th Timer',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['value'])) {
                            return $query->where('users.attendance_status', $data['value']);
                        }
                        return $query;
                    }),
                Tables\Filters\SelectFilter::make('consolidator_id')
                    ->label('Consolidator')
                    ->options(function () {
                        if (!Auth::check()) {
                            return [];
                        }
                        $user = Auth::user();
                        $query = \App\Models\User::query()
                            ->where(function ($q) {
                                $q->where('is_primary_leader', true)
                                  ->orWhere('is_network_admin', true);
                            });

                        // Gender filtering (except for super admins)
                        if (!$user->is_super_admin && $user->gender) {
                            $query->where('gender', $user->gender);
                        }

                        return $query->orderBy('first_name')
                            ->orderBy('last_name')
                            ->get()
                            ->mapWithKeys(function ($user) {
                                return [$user->id => $user->name];
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['value'])) {
                            return $query->where('consolidation_members.consolidator_id', $data['value']);
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        $id = is_object($record) ? ($record->id ?? null) : ($record['id'] ?? null);
                        return $id && !str_starts_with((string)$id, 'user_') && $id !== '0' && $id !== 0;
                    })
                    ->url(function ($record) {
                        $id = is_object($record) ? ($record->id ?? null) : ($record['id'] ?? null);
                        if ($id && !str_starts_with((string)$id, 'user_') && $id !== '0' && $id !== 0) {
                            return static::getUrl('edit', ['record' => $id]);
                        }
                        return null;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        $id = is_object($record) ? ($record->id ?? null) : ($record['id'] ?? null);
                        return $id && !str_starts_with((string)$id, 'user_') && $id !== '0' && $id !== 0;
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsolidations::route('/'),
            'create' => Pages\CreateConsolidation::route('/create'),
            'edit' => Pages\EditConsolidation::route('/{record}/edit'),
        ];
    }
}

