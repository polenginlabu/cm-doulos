<?php

namespace App\Console\Commands;

use App\Models\Discipleship;
use Illuminate\Console\Command;

class CleanupDuplicateDiscipleships extends Command
{
    protected $signature = 'discipleships:cleanup-duplicates';

    protected $description = 'Remove duplicate discipleship records for the same disciple, keeping only one active mentor per disciple';

    public function handle()
    {
        $this->info('Starting cleanup of duplicate discipleships...');
        $this->info('This will ensure each disciple has only ONE mentor (keeping the active one, or most recent if none active).');

        // Get all disciples who have multiple discipleship records
        $duplicates = Discipleship::select('disciple_id')
            ->groupBy('disciple_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('disciple_id');

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate discipleships found. Database is clean!');
            return Command::SUCCESS;
        }

        $this->info("Found {$duplicates->count()} disciples with multiple mentors.");
        $this->newLine();

        $totalCleaned = 0;
        $totalRemoved = 0;

        foreach ($duplicates as $discipleId) {
            // Get all discipleships for this disciple
            $discipleships = Discipleship::where('disciple_id', $discipleId)
                ->orderBy('status', 'desc') // active first
                ->orderBy('updated_at', 'desc') // most recently updated first
                ->orderBy('created_at', 'desc') // then most recently created
                ->get();

            if ($discipleships->count() <= 1) {
                continue;
            }

            // Find the one to keep (prefer active, then most recent)
            $toKeep = $discipleships->firstWhere('status', 'active') ?? $discipleships->first();

            // Get IDs of records to delete
            $toDelete = $discipleships->where('id', '!=', $toKeep->id)->pluck('id');

            if ($toDelete->isNotEmpty()) {
                $this->info("Disciple ID {$discipleId}:");
                $this->line("  ✓ Keeping: Discipleship ID {$toKeep->id} (mentor_id: {$toKeep->mentor_id}, status: {$toKeep->status})");

                // Show what will be deleted
                foreach ($discipleships->whereIn('id', $toDelete) as $toRemove) {
                    $this->line("  ✗ Removing: Discipleship ID {$toRemove->id} (mentor_id: {$toRemove->mentor_id}, status: {$toRemove->status})");
                }

                // Delete duplicate records
                $deletedCount = Discipleship::whereIn('id', $toDelete)->delete();
                $totalRemoved += $deletedCount;
                $totalCleaned++;
                $this->newLine();
            }
        }

        $this->info("Cleanup complete!");
        $this->info("Processed {$totalCleaned} disciples with duplicates");
        $this->info("Removed {$totalRemoved} duplicate discipleship records");

        return Command::SUCCESS;
    }
}

