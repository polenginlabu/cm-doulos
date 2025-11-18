<?php

namespace App\Filament\Resources\SuynlEnrollmentResource\Pages;

use App\Filament\Resources\SuynlEnrollmentResource;
use App\Models\SuynlEnrollment;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SuynlDashboard extends Page
{
    protected static string $resource = SuynlEnrollmentResource::class;

    protected static string $view = 'filament.resources.suynl-enrollment-resource.pages.suynl-dashboard';

    protected static ?string $title = 'SUYNL Dashboard';

    protected static ?string $navigationLabel = 'SUYNL Dashboard';

    public $enrollments = [];

    public function mount(): void
    {
        $this->loadEnrollments();
    }

    public function loadEnrollments(): void
    {
        $query = SuynlEnrollment::with(['user', 'leader', 'attendances']);

        // Filter by leader unless super admin or network admin
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user && !$user->is_super_admin && !$user->is_network_admin) {
                $query->where('leader_id', $user->id);
            }
        }

        // Only show enrollments that are not completed/finished
        $query->where('status', '!=', 'completed');

        $enrollments = $query->orderBy('enrolled_at', 'desc')->get();

        // Convert to array and add calculated fields
        $this->enrollments = $enrollments->map(function ($enrollment) {
            $data = $enrollment->toArray();
            $data['lessons_attended'] = $enrollment->lessons_attended;
            // Ensure attendances are properly serialized
            $data['attendances'] = $enrollment->attendances->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'lesson_number' => $attendance->lesson_number,
                    'is_present' => $attendance->is_present,
                    'attendance_date' => $attendance->attendance_date,
                ];
            })->toArray();
            return $data;
        })->toArray();
    }

    public function getTotalStudents(): int
    {
        return count($this->enrollments);
    }

    public function getCompletedCount(): int
    {
        return collect($this->enrollments)->where('status', 'completed')->count();
    }

    public function getActiveCount(): int
    {
        return collect($this->enrollments)->where('status', 'enrolled')->count();
    }

    public function getAverageProgress(): float
    {
        if (empty($this->enrollments)) {
            return 0;
        }

        $totalProgress = collect($this->enrollments)->sum(function ($enrollment) {
            return $enrollment['lessons_attended'] ?? 0;
        });

        return ($totalProgress / (count($this->enrollments) * 10)) * 100;
    }

    public function getLessonTitles(): array
    {
        return [
            1 => 'Salvation',
            2 => 'Repentance',
            3 => 'Lordship',
            4 => 'Forgiveness',
            5 => 'Lifestyle – The 4 Greatest Meetings',
            6 => 'Devotional Life',
            7 => 'Your Active Life of Prayer',
            8 => 'Witnessing – Sharing Your New Life with Others',
            9 => 'Life Of Obedience (Surrender To God\'s Will)',
            10 => 'Life In The Church (Belongingness In The Church)',
        ];
    }

    public function deleteEnrollment($enrollmentId): void
    {
        $enrollment = SuynlEnrollment::find($enrollmentId);

        if ($enrollment) {
            $enrollment->delete();
            Notification::make()
                ->title('Enrollment deleted')
                ->success()
                ->send();
            $this->loadEnrollments();
        }
    }

    public function toggleLessonAttendance($enrollmentId, $lessonNumber): void
    {
        $enrollment = SuynlEnrollment::find($enrollmentId);

        if (!$enrollment) {
            Notification::make()
                ->title('Enrollment not found')
                ->danger()
                ->send();
            return;
        }

        // Don't allow changes if already completed
        if ($enrollment->status === 'completed') {
            Notification::make()
                ->title('This enrollment is already completed')
                ->warning()
                ->send();
            return;
        }

        // Check if attendance already exists
        $attendance = \App\Models\SuynlAttendance::where('suynl_enrollment_id', $enrollmentId)
            ->where('lesson_number', $lessonNumber)
            ->first();

        if ($attendance) {
            // If exists and is present, remove it
            if ($attendance->is_present) {
                $attendance->delete();
                Notification::make()
                    ->title('Lesson attendance removed')
                    ->success()
                    ->send();
            } else {
                // If exists but not present, mark as present
                $attendance->update([
                    'is_present' => true,
                    'attendance_date' => now(),
                ]);
                Notification::make()
                    ->title('Lesson marked as attended')
                    ->success()
                    ->send();
            }
        } else {
            // Create new attendance record
            \App\Models\SuynlAttendance::create([
                'suynl_enrollment_id' => $enrollmentId,
                'lesson_number' => $lessonNumber,
                'attendance_date' => now(),
                'is_present' => true,
            ]);
            Notification::make()
                ->title('Lesson marked as attended')
                ->success()
                ->send();
        }

        $this->loadEnrollments();
    }

    public function finishEnrollment($enrollmentId): void
    {
        $enrollment = SuynlEnrollment::find($enrollmentId);

        if (!$enrollment) {
            Notification::make()
                ->title('Enrollment not found')
                ->danger()
                ->send();
            return;
        }

        // Check if all lessons are completed
        $lessonsAttended = $enrollment->lessons_attended;
        if ($lessonsAttended < 10) {
            Notification::make()
                ->title('Cannot finish enrollment')
                ->body('All 10 lessons must be completed before finishing.')
                ->warning()
                ->send();
            return;
        }

        // Mark as completed
        $enrollment->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Notification::make()
            ->title('Enrollment completed')
            ->body('The enrollment has been marked as completed and will no longer appear on the dashboard.')
            ->success()
            ->send();

        $this->loadEnrollments();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('addStudent')
                ->label('Add Student')
                ->icon('heroicon-o-user-plus')
                ->url(fn () => SuynlEnrollmentResource::getUrl('create')),
            Actions\Action::make('viewList')
                ->label('View List')
                ->icon('heroicon-o-list-bullet')
                ->url(fn () => SuynlEnrollmentResource::getUrl('list')),
        ];
    }
}

