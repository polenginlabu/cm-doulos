<?php

namespace App\Jobs;

use App\Mail\SuynlLeaderReminder;
use App\Models\SuynlEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSuynlLeaderReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SendSuynlLeaderReminders job started');

        // Load all active (non-completed) SUYNL enrollments with leaders and students
        $enrollments = SuynlEnrollment::query()
            ->where('status', 'enrolled')
            ->with(['leader', 'user', 'attendances'])
            ->get();

        if ($enrollments->isEmpty()) {
            Log::info('SendSuynlLeaderReminders: no active enrollments found');
            return;
        }

        // Group enrollments by leader
        $enrollmentsByLeader = $enrollments->groupBy('leader_id');

        $lessonTitles = [
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

        foreach ($enrollmentsByLeader as $leaderId => $leaderEnrollments) {
            $leader = optional($leaderEnrollments->first())->leader;

            if (!$leader || !$leader->email) {
                Log::info('SendSuynlLeaderReminders: skipping leader without email', [
                    'leader_id' => $leaderId,
                ]);
                continue;
            }

            $students = [];

            foreach ($leaderEnrollments as $enrollment) {
                $student = $enrollment->user;
                if (!$student) {
                    continue;
                }

                // Determine next lesson number based on attended lessons
                $attendedLessons = $enrollment->attendances
                    ->where('is_present', true)
                    ->pluck('lesson_number')
                    ->unique()
                    ->values()
                    ->all();

                $nextLessonNumber = null;
                for ($i = 1; $i <= 10; $i++) {
                    if (!in_array($i, $attendedLessons, true)) {
                        $nextLessonNumber = $i;
                        break;
                    }
                }

                // If no next lesson (all done), skip this student
                if ($nextLessonNumber === null) {
                    continue;
                }

                $students[] = [
                    'student' => $student,
                    'enrollment' => $enrollment,
                    'next_lesson_number' => $nextLessonNumber,
                    'next_lesson_title' => $lessonTitles[$nextLessonNumber] ?? null,
                    'lessons_attended' => $enrollment->lessons_attended,
                ];
            }

            if (empty($students)) {
                Log::info('SendSuynlLeaderReminders: leader has no students with pending lessons', [
                    'leader_id' => $leaderId,
                ]);
                continue;
            }

            Log::info('SendSuynlLeaderReminders: queuing email for SUYNL leader', [
                'leader_id' => $leaderId,
                'student_count' => count($students),
            ]);

            Mail::to($leader->email)->queue(
                new SuynlLeaderReminder(
                    leader: $leader,
                    students: $students
                )
            );
        }
    }
}


