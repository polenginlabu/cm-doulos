<?php

namespace App\Filament\Resources\TrainingEnrollmentResource\Pages;

use App\Filament\Resources\TrainingEnrollmentResource;
use App\Models\TrainingEnrollment;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTrainingEnrollments extends ListRecords
{
    protected static string $resource = TrainingEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('bulkEnroll')
                ->label('Bulk Enroll')
                ->icon('heroicon-o-user-plus')
                ->form([
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
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set) {
                            $set('batch_id', null);
                        })
                        ->helperText('Select the training program'),
                    Forms\Components\Select::make('batch_id')
                        ->label('Batch')
                        ->options(function ($get) {
                            $trainingId = $get('training_id');
                            if (!$trainingId) {
                                return [];
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
                            if (!$trainingId) {
                                return [];
                            }

                            return \App\Models\TrainingBatch::where('training_id', $trainingId)
                                ->where('is_active', true)
                                ->where(function ($q) use ($search) {
                                    $q->where('name', 'like', "%{$search}%")
                                      ->orWhere('code', 'like', "%{$search}%");
                                })
                                ->orderBy('name')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function ($batch) {
                                    return [$batch->id => $batch->name . ($batch->code ? ' (' . $batch->code . ')' : '')];
                                })
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value) {
                            $batch = \App\Models\TrainingBatch::find($value);
                            if ($batch) {
                                return $batch->name;
                            }
                            return null;
                        })
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->helperText('Select the training batch')
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
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Select one or more students to enroll'),
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
                ])
                ->action(function (array $data): void {
                    $trainingId = $data['training_id'];
                    $batchId = $data['batch_id'] ?? null;
                    $userIds = $data['user_ids'];
                    $enrolledAt = $data['enrolled_at'];
                    $status = $data['status'];

                    // Validate batch is required
                    if (!$batchId) {
                        Notification::make()
                            ->title('Batch required')
                            ->body('Please select a training batch.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $enrolled = 0;
                    $skipped = 0;
                    $skippedNames = [];

                    foreach ($userIds as $userId) {
                        // Check if already enrolled in this batch
                        $existing = TrainingEnrollment::where('training_id', $trainingId)
                            ->where('batch_id', $batchId)
                            ->where('user_id', $userId)
                            ->where('status', '!=', 'dropped')
                            ->exists();

                        if ($existing) {
                            $skipped++;
                            $user = \App\Models\User::find($userId);
                            if ($user) {
                                $skippedNames[] = $user->name;
                            }
                            continue;
                        }

                        // Create enrollment
                        TrainingEnrollment::create([
                            'training_id' => $trainingId,
                            'batch_id' => $batchId,
                            'user_id' => $userId,
                            'enrolled_at' => $enrolledAt,
                            'status' => $status,
                        ]);

                        $enrolled++;
                    }

                    // Show notification
                    $message = "Successfully enrolled {$enrolled} student(s).";
                    if ($skipped > 0) {
                        $message .= " {$skipped} student(s) were skipped (already enrolled).";
                    }

                    Notification::make()
                        ->title($message)
                        ->success()
                        ->send();

                    // Refresh the table
                    $this->resetTable();
                })
                ->modalHeading('Bulk Enroll Students')
                ->modalDescription('Enroll multiple students in a training at once.')
                ->modalSubmitActionLabel('Enroll Students'),
        ];
    }
}

