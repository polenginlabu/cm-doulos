<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingEnrollmentResource\Pages;
use App\Filament\Resources\TrainingEnrollmentResource\RelationManagers;
use App\Models\TrainingEnrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TrainingEnrollmentResource extends Resource
{
    protected static ?string $model = TrainingEnrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Training Enrollments';

    protected static ?string $navigationGroup = 'Training';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Enrollment Information')
                    ->schema([
                        Forms\Components\Select::make('training_id')
                            ->label('Training')
                            ->options(function () {
                                return \App\Models\Training::where('is_active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($training) {
                                        return [$training->id => $training->name . ' (' . $training->code . ')'];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                $set('batch_id', null);
                            })
                            ->helperText('Select the training program (optional if batch is selected)'),
                        Forms\Components\Select::make('batch_id')
                            ->label('Batch')
                            ->options(function ($get) {
                                $trainingId = $get('training_id');
                                if (!$trainingId) {
                                    // If no training selected, show all active batches
                                    return \App\Models\TrainingBatch::where('is_active', true)
                                        ->with('training')
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(function ($batch) {
                                            return [$batch->id => $batch->training->name . ' - ' . $batch->name];
                                        })
                                        ->toArray();
                                }

                                return \App\Models\TrainingBatch::where('training_id', $trainingId)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($batch) {
                                        return [$batch->id => $batch->name . ($batch->code ? ' (' . $batch->code . ')' : '')];
                                    })
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search, $get) {
                                $trainingId = $get('training_id');
                                $query = \App\Models\TrainingBatch::where('is_active', true)
                                    ->with('training')
                                    ->where(function ($q) use ($search) {
                                        $q->where('name', 'like', "%{$search}%")
                                          ->orWhere('code', 'like', "%{$search}%")
                                          ->orWhereHas('training', function ($q) use ($search) {
                                              $q->where('name', 'like', "%{$search}%");
                                          });
                                    });

                                if ($trainingId) {
                                    $query->where('training_id', $trainingId);
                                }

                                return $query->orderBy('name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($batch) {
                                        return [$batch->id => $batch->training->name . ' - ' . $batch->name];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $batch = \App\Models\TrainingBatch::with('training')->find($value);
                                if ($batch) {
                                    return $batch->training->name . ' - ' . $batch->name;
                                }
                                return null;
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $batch = \App\Models\TrainingBatch::find($state);
                                    if ($batch) {
                                        $set('training_id', $batch->training_id);
                                    }
                                }
                            })
                            ->helperText('Select the training batch (e.g., Q1 2025, Batch 1)')
                            ->required(),
                        Forms\Components\Select::make('user_ids')
                            ->label('Students')
                            ->multiple()
                            ->options(function () {
                                return \App\Models\User::query()
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
                                return \App\Models\User::query()
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
                            ->helperText('Select one or more students to enroll')
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\TrainingEnrollmentResource\Pages\CreateTrainingEnrollment),
                        Forms\Components\Select::make('user_id')
                            ->label('Student')
                            ->options(function () {
                                return \App\Models\User::query()
                                    ->orderBy('first_name')
                                    ->orderBy('last_name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        return [$user->id => $user->name];
                                    })
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\User::query()
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
                            ->helperText('Select the student')
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\TrainingEnrollmentResource\Pages\EditTrainingEnrollment),
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
                        Forms\Components\DatePicker::make('completed_at')
                            ->label('Completed Date')
                            ->visible(fn ($get) => $get('status') === 'completed'),
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
                Tables\Columns\TextColumn::make('training.name')
                    ->label('Training')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch.name')
                    ->label('Batch')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No batch'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('enrolled_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lessons_attended')
                    ->label('Lessons Attended')
                    ->getStateUsing(function ($record) {
                        $attended = $record->attendances()->where('is_present', true)->count();
                        $total = $record->training->total_lessons ?? 0;
                        return "{$attended} / {$total}";
                    })
                    ->sortable(false),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        return number_format($record->progress_percentage, 1) . '%';
                    })
                    ->sortable(false),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'enrolled',
                        'primary' => 'completed',
                        'warning' => 'dropped',
                    ]),
                Tables\Columns\TextColumn::make('completed_at')
                    ->date()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('training_id')
                    ->label('Training')
                    ->options(function () {
                        return \App\Models\Training::orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($training) {
                                return [$training->id => $training->name];
                            })
                            ->toArray();
                    })
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'enrolled' => 'Enrolled',
                        'completed' => 'Completed',
                        'dropped' => 'Dropped',
                    ]),
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
            RelationManagers\AttendancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingEnrollments::route('/'),
            'create' => Pages\CreateTrainingEnrollment::route('/create'),
            'edit' => Pages\EditTrainingEnrollment::route('/{record}/edit'),
        ];
    }
}

