<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingAttendanceResource\Pages;
use App\Models\TrainingAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrainingAttendanceResource extends Resource
{
    protected static ?string $model = TrainingAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Training Attendance';

    protected static ?string $navigationGroup = 'Training';

    protected static ?int $navigationSort = 3;

    /**
     * Get filtered user IDs based on network and gender.
     */
    protected static function getFilteredUserIds(): array
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return [];
        }

        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        // Super admins and network admins can see all users
        if ($user->is_super_admin || $user->is_network_admin) {
            $query = \App\Models\User::query();
        } else {
            // Regular users can only see their network
            if (!method_exists($user, 'getNetworkUserIds')) {
                return [$user->id];
            }
            $networkIds = $user->getNetworkUserIds();
            $query = \App\Models\User::whereIn('id', $networkIds);
        }

        // Filter by gender (same gender only)
        if ($user->gender) {
            $query->where('gender', $user->gender);
        }

        return $query->pluck('id')->toArray();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // If user is logged in, filter by network and gender unless they're a super admin or network admin
        if (\Illuminate\Support\Facades\Auth::check()) {
            /** @var \App\Models\User $user */
            $user = \Illuminate\Support\Facades\Auth::user();

            // Super admins and network admins can see all attendances
            if ($user && ($user->is_super_admin || $user->is_network_admin)) {
                return $query;
            }

            // Regular users can only see attendances from their network
            $filteredUserIds = static::getFilteredUserIds();
            if (!empty($filteredUserIds)) {
                $query->whereHas('enrollment', function ($q) use ($filteredUserIds) {
                    $q->whereIn('user_id', $filteredUserIds);
                });
            } else {
                // If no filtered users, show nothing
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attendance Information')
                    ->schema([
                        Forms\Components\Select::make('training_enrollment_id')
                            ->label('Enrollment')
                            ->options(function () {
                                $filteredUserIds = static::getFilteredUserIds();
                                if (empty($filteredUserIds)) {
                                    return [];
                                }
                                return \App\Models\TrainingEnrollment::with(['user', 'training', 'batch'])
                                    ->whereIn('user_id', $filteredUserIds)
                                    ->orderBy('enrolled_at', 'desc')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(function ($enrollment) {
                                        $batchInfo = $enrollment->batch ? ' - ' . $enrollment->batch->name : '';
                                        $label = $enrollment->user->name . ' - ' . $enrollment->training->name . $batchInfo;
                                        return [$enrollment->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                $filteredUserIds = static::getFilteredUserIds();
                                if (empty($filteredUserIds)) {
                                    return [];
                                }
                                return \App\Models\TrainingEnrollment::with(['user', 'training', 'batch'])
                                    ->whereIn('user_id', $filteredUserIds)
                                    ->where(function ($q) use ($search) {
                                        $q->whereHas('user', function ($q2) use ($search) {
                                            $q2->where('first_name', 'like', "%{$search}%")
                                              ->orWhere('last_name', 'like', "%{$search}%");
                                        })
                                        ->orWhereHas('training', function ($q2) use ($search) {
                                            $q2->where('name', 'like', "%{$search}%")
                                              ->orWhere('code', 'like', "%{$search}%");
                                        })
                                        ->orWhereHas('batch', function ($q2) use ($search) {
                                            $q2->where('name', 'like', "%{$search}%")
                                              ->orWhere('code', 'like', "%{$search}%");
                                        });
                                    })
                                    ->orderBy('enrolled_at', 'desc')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($enrollment) {
                                        $batchInfo = $enrollment->batch ? ' - ' . $enrollment->batch->name : '';
                                        $label = $enrollment->user->name . ' - ' . $enrollment->training->name . $batchInfo;
                                        return [$enrollment->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $enrollment = \App\Models\TrainingEnrollment::with(['user', 'training', 'batch'])->find($value);
                                if ($enrollment) {
                                    $batchInfo = $enrollment->batch ? ' - ' . $enrollment->batch->name : '';
                                    return $enrollment->user->name . ' - ' . $enrollment->training->name . $batchInfo;
                                }
                                return null;
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if ($state) {
                                    $enrollment = \App\Models\TrainingEnrollment::with('training')->find($state);
                                    if ($enrollment && $enrollment->training) {
                                        // Set max lesson number based on training
                                        // This will be used for validation
                                    }
                                }
                            })
                            ->helperText('Select the training enrollment'),
                        Forms\Components\TextInput::make('lesson_number')
                            ->label('Lesson Number')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(function ($get) {
                                $enrollmentId = $get('training_enrollment_id');
                                if ($enrollmentId) {
                                    $enrollment = \App\Models\TrainingEnrollment::with('training')->find($enrollmentId);
                                    if ($enrollment && $enrollment->training) {
                                        return $enrollment->training->total_lessons;
                                    }
                                }
                                return 100; // Default max
                            })
                            ->helperText('Lesson number (1 to total lessons)')
                            ->rules([
                                function ($get, $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        if ($value && $get('training_enrollment_id')) {
                                            // Check if this lesson is already recorded for this enrollment
                                            $query = \App\Models\TrainingAttendance::where('training_enrollment_id', $get('training_enrollment_id'))
                                                ->where('lesson_number', $value);

                                            // Exclude current record when editing
                                            if ($record) {
                                                $query->where('id', '!=', $record->id);
                                            }

                                            if ($query->exists()) {
                                                $fail('Attendance for this lesson has already been recorded for this enrollment.');
                                            }
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\DatePicker::make('attendance_date')
                            ->label('Attendance Date')
                            ->default(now())
                            ->required(),
                        Forms\Components\Toggle::make('is_present')
                            ->label('Present')
                            ->default(true)
                            ->helperText('Mark as present or absent'),
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
                Tables\Columns\TextColumn::make('enrollment.user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('enrollment.training.name')
                    ->label('Training')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('enrollment.batch.name')
                    ->label('Batch')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No batch'),
                Tables\Columns\TextColumn::make('lesson_number')
                    ->label('Lesson')
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_present')
                    ->label('Present')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('training_enrollment_id')
                    ->label('Enrollment')
                    ->options(function () {
                        $filteredUserIds = static::getFilteredUserIds();
                        if (empty($filteredUserIds)) {
                            return [];
                        }
                        return \App\Models\TrainingEnrollment::with(['user', 'training', 'batch'])
                            ->whereIn('user_id', $filteredUserIds)
                            ->orderBy('enrolled_at', 'desc')
                            ->get()
                            ->mapWithKeys(function ($enrollment) {
                                $batchInfo = $enrollment->batch ? ' - ' . $enrollment->batch->name : '';
                                $label = $enrollment->user->name . ' - ' . $enrollment->training->name . $batchInfo;
                                return [$enrollment->id => $label];
                            })
                            ->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search) {
                        $filteredUserIds = static::getFilteredUserIds();
                        if (empty($filteredUserIds)) {
                            return [];
                        }
                        return \App\Models\TrainingEnrollment::with(['user', 'training', 'batch'])
                            ->whereIn('user_id', $filteredUserIds)
                            ->where(function ($q) use ($search) {
                                $q->whereHas('user', function ($q2) use ($search) {
                                    $q2->where('first_name', 'like', "%{$search}%")
                                      ->orWhere('last_name', 'like', "%{$search}%");
                                })
                                ->orWhereHas('training', function ($q2) use ($search) {
                                    $q2->where('name', 'like', "%{$search}%");
                                })
                                ->orWhereHas('batch', function ($q2) use ($search) {
                                    $q2->where('name', 'like', "%{$search}%");
                                });
                            })
                            ->orderBy('enrolled_at', 'desc')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($enrollment) {
                                $batchInfo = $enrollment->batch ? ' - ' . $enrollment->batch->name : '';
                                $label = $enrollment->user->name . ' - ' . $enrollment->training->name . $batchInfo;
                                return [$enrollment->id => $label];
                            })
                            ->toArray();
                    })
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_present')
                    ->label('Present'),
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
            ->defaultSort('attendance_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingAttendances::route('/'),
            'create' => Pages\CreateTrainingAttendance::route('/create'),
            'edit' => Pages\EditTrainingAttendance::route('/{record}/edit'),
        ];
    }
}

