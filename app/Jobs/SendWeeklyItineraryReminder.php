<?php

namespace App\Jobs;

use App\Mail\ItineraryWeeklyReminder;
use App\Models\ItineraryItem;
use App\Models\User;
use App\Notifications\ItineraryWeeklyReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklyItineraryReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $usersWithPlans = ItineraryItem::query()
            ->where('week_start_date', $weekStart)
            ->distinct()
            ->pluck('user_id');

        $users = User::query()
            ->where('is_active', true)
            ->whereNotIn('id', $usersWithPlans)
            ->get();

        if ($users->isEmpty()) {
            Log::info('SendWeeklyItineraryReminder: all users already have plans');
            return;
        }

        foreach ($users as $user) {
            if ($user->email) {
                Mail::to($user->email)->queue(new ItineraryWeeklyReminder(user: $user));
            }

            $user->notify(new ItineraryWeeklyReminderNotification());
        }
    }
}
