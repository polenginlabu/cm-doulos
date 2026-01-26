<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ItineraryDailyOverviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Carbon $date,
        protected array $activities,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Your itinerary for today',
            'body' => sprintf(
                'You have %d activity%s scheduled on %s.',
                count($this->activities),
                count($this->activities) === 1 ? '' : 'ies',
                $this->date->format('M d')
            ),
        ];
    }
}
