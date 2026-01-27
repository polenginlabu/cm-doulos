<?php

namespace App\Jobs;

use App\Mail\ItineraryDailyOverview;
use App\Models\ItineraryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyItineraryOverview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = Carbon::now();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $dayIndex = $today->dayOfWeekIso - 1;

        $itemsByUser = ItineraryItem::query()
            ->with(['activity', 'user'])
            ->where('week_start_date', $weekStart)
            ->where('day_of_week', $dayIndex)
            ->get()
            ->groupBy('user_id');


        if ($itemsByUser->isEmpty()) {
            Log::info('SendDailyItineraryOverview: no items found for today');
            return;
        }

        foreach ($itemsByUser as $userId => $items) {
            $user = $items->first()?->user;

            if (!$user) {
                continue;
            }

            $activityNames = $items
                ->map(fn ($item) => $item->custom_label ?? $item->activity?->name ?? 'Untitled')
                ->values()
                ->all();

            if ($user->email) {
                 Mail::to($user->email)->queue(
                    new ItineraryDailyOverview(
                        user: $user,
                        date: $today,
                        activities: $activityNames
                    )
                );
            }
        }
    }
}
