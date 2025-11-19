<?php

namespace App\Jobs;

use App\Mail\ConsolidatorDailyReminder;
use App\Models\ConsolidationMember;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendConsolidatorDailyReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SendConsolidatorDailyReminders job started');

        // Find all consolidators that have at least one consolidation member assigned
        $consolidatorIds = ConsolidationMember::query()
            ->whereNotNull('consolidator_id')
            ->distinct()
            ->pluck('consolidator_id');

        if ($consolidatorIds->isEmpty()) {
            Log::info('SendConsolidatorDailyReminders: no consolidators found with consolidation members');
            return;
        }

        $consolidators = User::query()
            ->whereIn('id', $consolidatorIds)
            ->whereNotNull('email')
            ->get();

        Log::info('SendConsolidatorDailyReminders: consolidators loaded', [
            'count' => $consolidators->count(),
            'ids' => $consolidators->pluck('id')->all(),
        ]);

        foreach ($consolidators as $consolidator) {
            // Get consolidation members that still need attention for this consolidator
            $pendingMembers = ConsolidationMember::query()
                ->where('consolidator_id', $consolidator->id)
                ->whereIn('status', [
                    'not_contacted',
                    'contacted',
                    'in_progress',
                    'follow_up_scheduled',
                ])
                ->with('user')
                ->get();

            $pendingCount = $pendingMembers->count();

            if ($pendingCount === 0) {
                Log::info('SendConsolidatorDailyReminders: consolidator has no pending members, skipping', [
                    'consolidator_id' => $consolidator->id,
                ]);
                continue;
            }

            Log::info('SendConsolidatorDailyReminders: queuing email for consolidator', [
                'consolidator_id' => $consolidator->id,
                'pending_count' => $pendingCount,
            ]);

            Mail::to($consolidator->email)->queue(
                new ConsolidatorDailyReminder(
                    consolidator: $consolidator,
                    pendingMembers: $pendingMembers
                )
            );
        }
    }
}


