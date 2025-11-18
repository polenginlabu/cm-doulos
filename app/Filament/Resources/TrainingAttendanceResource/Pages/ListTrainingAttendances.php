<?php

namespace App\Filament\Resources\TrainingAttendanceResource\Pages;

use App\Filament\Resources\TrainingAttendanceResource;
use App\Models\TrainingAttendance;
use App\Models\TrainingEnrollment;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTrainingAttendances extends ListRecords
{
    protected static string $resource = TrainingAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('bulkAttendance')
                ->label('Bulk Mark Attendance')
                ->icon('heroicon-o-clipboard-document-check')
                ->form([
                    Forms\Components\Select::make('batch_id')
                        ->label('Batch')
                        ->options(function () {
                            return \App\Models\TrainingBatch::where('is_active', true)
                                ->with('training')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(function ($batch) {
                                    return [$batch->id => $batch->training->name . ' - ' . $batch->name];
                                })
                                ->toArray();
                        })
                        ->getSearchResultsUsing(function (string $search) {
                            return \App\Models\TrainingBatch::where('is_active', true)
                                ->with('training')
                                ->where(function ($q) use ($search) {
                                    $q->where('name', 'like', "%{$search}%")
                                      ->orWhere('code', 'like', "%{$search}%")
                                      ->orWhereHas('training', function ($q) use ($search) {
                                          $q->where('name', 'like', "%{$search}%");
                                      });
                                })
                                ->orderBy('name')
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
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set) {
                            $set('enrollment_ids', []);
                        })
                        ->helperText('Select the training batch'),
                    Forms\Components\Select::make('enrollment_ids')
                        ->label('Enrollments')
                        ->multiple()
                        ->options(function ($get) {
                            $batchId = $get('batch_id');
                            if (!$batchId) {
                                return [];
                            }

                            return TrainingEnrollment::where('batch_id', $batchId)
                                ->where('status', 'enrolled')
                                ->with(['user', 'training', 'batch'])
                                ->orderBy('enrolled_at', 'desc')
                                ->get()
                                ->mapWithKeys(function ($enrollment) {
                                    $label = $enrollment->user->name . ' - ' . ($enrollment->batch ? $enrollment->batch->name : $enrollment->training->name);
                                    return [$enrollment->id => $label];
                                })
                                ->toArray();
                        })
                        ->getSearchResultsUsing(function (string $search, $get) {
                            $batchId = $get('batch_id');
                            if (!$batchId) {
                                return [];
                            }

                            return TrainingEnrollment::where('batch_id', $batchId)
                                ->where('status', 'enrolled')
                                ->with(['user', 'training', 'batch'])
                                ->whereHas('user', function ($q) use ($search) {
                                    $q->where('first_name', 'like', "%{$search}%")
                                      ->orWhere('last_name', 'like', "%{$search}%");
                                })
                                ->orderBy('enrolled_at', 'desc')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function ($enrollment) {
                                    $label = $enrollment->user->name . ' - ' . ($enrollment->batch ? $enrollment->batch->name : $enrollment->training->name);
                                    return [$enrollment->id => $label];
                                })
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Select one or more enrollments'),
                    Forms\Components\TextInput::make('lesson_number')
                        ->label('Lesson Number')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(function ($get) {
                            $trainingId = $get('training_id');
                            if ($trainingId) {
                                $training = \App\Models\Training::find($trainingId);
                                if ($training) {
                                    return $training->total_lessons;
                                }
                            }
                            return 100;
                        })
                        ->helperText('Lesson number to mark attendance for'),
                    Forms\Components\DatePicker::make('attendance_date')
                        ->label('Attendance Date')
                        ->default(now())
                        ->required(),
                    Forms\Components\Toggle::make('is_present')
                        ->label('Mark as Present')
                        ->default(true)
                        ->helperText('Mark all selected students as present or absent'),
                ])
                ->action(function (array $data): void {
                    $enrollmentIds = $data['enrollment_ids'];
                    $lessonNumber = $data['lesson_number'];
                    $attendanceDate = $data['attendance_date'];
                    $isPresent = $data['is_present'] ?? true;

                    $created = 0;
                    $skipped = 0;

                    foreach ($enrollmentIds as $enrollmentId) {
                        // Check if attendance already exists for this enrollment and lesson
                        $existing = TrainingAttendance::where('training_enrollment_id', $enrollmentId)
                            ->where('lesson_number', $lessonNumber)
                            ->exists();

                        if ($existing) {
                            $skipped++;
                            continue;
                        }

                        // Create attendance record
                        TrainingAttendance::create([
                            'training_enrollment_id' => $enrollmentId,
                            'lesson_number' => $lessonNumber,
                            'attendance_date' => $attendanceDate,
                            'is_present' => $isPresent,
                        ]);

                        $created++;
                    }

                    // Show notification
                    $message = "Successfully marked attendance for {$created} student(s).";
                    if ($skipped > 0) {
                        $message .= " {$skipped} student(s) were skipped (attendance already recorded for this lesson).";
                    }

                    Notification::make()
                        ->title($message)
                        ->success()
                        ->send();

                    // Refresh the table
                    $this->resetTable();
                })
                ->modalHeading('Bulk Mark Attendance')
                ->modalDescription('Mark attendance for multiple students in a training lesson at once.')
                ->modalSubmitActionLabel('Mark Attendance'),
        ];
    }
}

