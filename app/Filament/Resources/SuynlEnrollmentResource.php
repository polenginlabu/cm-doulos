<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuynlEnrollmentResource\Pages;
use App\Filament\Resources\SuynlEnrollmentResource\RelationManagers;
use App\Filament\Forms\Components\UserSelect;
use App\Models\Discipleship;
use App\Models\SuynlEnrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SuynlEnrollmentResource extends Resource
{
    protected static ?string $model = SuynlEnrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'SUYNL';

    protected static ?string $modelLabel = 'SUYNL Enrollment';

    protected static ?string $pluralModelLabel = 'SUYNL Enrollments';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationGroup = 'Training';

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
        $query = parent::getEloquentQuery();

        // If user is logged in, filter by leader unless they're a super admin or network admin
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Super admins and network admins can see all enrollments
            if ($user && ($user->is_super_admin || $user->is_network_admin)) {
                return $query;
            }

            // Regular users can see enrollments where:
            // 1. They are the leader, OR
            // 2. The student is one of their disciples
            if ($user) {
                // Get all disciple IDs (students they are mentoring)
                $discipleIds = Discipleship::where('mentor_id', $user->id)
                    ->where('status', 'active')
                    ->pluck('disciple_id')
                    ->toArray();

                // Build OR condition: leader_id = user.id OR user_id IN (disciple_ids)
                $query->where(function ($q) use ($user, $discipleIds) {
                    $q->where('leader_id', $user->id);

                    // If user has disciples, also show enrollments where student is their disciple
                    if (!empty($discipleIds)) {
                        $q->orWhereIn('user_id', $discipleIds);
                    }
                });
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Enrollment Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Student')
                            ->options(function () {
                                $filteredUserIds = static::getFilteredUserIds();
                                if (empty($filteredUserIds)) {
                                    return [];
                                }
                                return \App\Models\User::whereIn('id', $filteredUserIds)
                                    ->orderBy('first_name')
                                    ->orderBy('last_name')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        return [$user->id => $user->name];
                                    })
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                $filteredUserIds = static::getFilteredUserIds();
                                if (empty($filteredUserIds)) {
                                    return [];
                                }
                                return \App\Models\User::whereIn('id', $filteredUserIds)
                                    ->where(function ($q) use ($search) {
                                        $q->where('first_name', 'like', "%{$search}%")
                                          ->orWhere('last_name', 'like', "%{$search}%");
                                    })
                                    ->orderBy('first_name')
                                    ->orderBy('last_name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        return [$user->id => $user->name];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\User::find($value)?->name)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the student (1st to 4th timer)'),
                        UserSelect::make('leader_id', [
                            'label' => 'Leader',
                            'excludePrimaryLeader' => true,
                            'excludeCurrentUser' => false,
                            'activeOnly' => false,
                            'allowEmptySearch' => false,
                            'limit' => 100,
                        ])
                            ->default(fn () => Auth::id())
                            ->required()
                            ->helperText('The leader conducting the SUYNL training. Only members from your network (excluding primary leaders) are shown.'),
                        Forms\Components\DatePicker::make('enrolled_at')
                            ->label('Enrollment Date')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'enrolled' => 'Enrolled',
                                'completed' => 'Completed',
                                'dropped' => 'Dropped',
                            ])
                            ->default('enrolled')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leader.name')
                    ->label('Leader')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lessons_attended')
                    ->label('Lessons Attended')
                    ->getStateUsing(fn ($record) => $record->lessons_attended . ' / 10')
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn ($state) => number_format($state, 0) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'enrolled',
                        'danger' => 'dropped',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('enrolled_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'enrolled' => 'Enrolled',
                        'completed' => 'Completed',
                        'dropped' => 'Dropped',
                    ]),
                Tables\Filters\SelectFilter::make('leader_id')
                    ->label('Leader')
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
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('enrolled_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\SuynlEnrollmentResource\RelationManagers\AttendancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\SuynlDashboard::route('/'),
            'list' => Pages\ListSuynlEnrollments::route('/list'),
            'create' => Pages\CreateSuynlEnrollment::route('/create'),
            'edit' => Pages\EditSuynlEnrollment::route('/{record}/edit'),
        ];
    }
}

