<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Discipleship;
use Illuminate\Console\Command;

class RebuildUserTree extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:rebuild-tree {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild the user tree by setting primary_user_id for all users based on their mentor hierarchy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting tree rebuild...');

        // Get all active discipleships
        $discipleships = Discipleship::where('status', 'active')
            ->with(['mentor', 'disciple'])
            ->get();

        // Build mentor -> disciples map
        $mentorMap = [];
        foreach ($discipleships as $discipleship) {
            $mentorMap[$discipleship->disciple_id] = $discipleship->mentor_id;
        }

        $updated = 0;
        $skipped = 0;

        // Process all users
        $users = User::all();

        foreach ($users as $user) {
            $primaryUserId = $this->findPrimaryUser($user->id, $mentorMap);

            if ($primaryUserId && $user->primary_user_id != $primaryUserId) {
                if (!$dryRun) {
                    $user->primary_user_id = $primaryUserId;
                    $user->saveQuietly();
                }
                $this->line("User {$user->name} (ID: {$user->id}): Set primary_user_id to {$primaryUserId}");
                $updated++;
            } elseif (!$primaryUserId && $user->primary_user_id) {
                // User has a primary_user_id but shouldn't (no path to primary leader)
                if (!$dryRun) {
                    $user->primary_user_id = null;
                    $user->saveQuietly();
                }
                $this->line("User {$user->name} (ID: {$user->id}): Removed primary_user_id (no path to primary leader)");
                $updated++;
            } else {
                $skipped++;
            }
        }

        $this->info("\nTree rebuild complete!");
        $this->info("Updated: {$updated} users");
        $this->info("Skipped: {$skipped} users (already correct)");

        if ($dryRun) {
            $this->warn("\nThis was a dry run. Run without --dry-run to apply changes.");
        }

        return 0;
    }

    /**
     * Find the primary user for a given user by traversing up the mentor chain
     */
    private function findPrimaryUser(int $userId, array $mentorMap): ?int
    {
        $visited = [];
        $currentId = $userId;

        // Traverse up the mentor chain
        while (isset($mentorMap[$currentId])) {
            // Prevent infinite loops
            if (isset($visited[$currentId])) {
                break;
            }
            $visited[$currentId] = true;

            $mentorId = $mentorMap[$currentId];
            $mentor = User::find($mentorId);

            if (!$mentor) {
                break;
            }

            // If mentor is a primary leader, return their ID
            if ($mentor->is_primary_leader) {
                return $mentorId;
            }

            // If mentor has a primary_user_id, return that
            if ($mentor->primary_user_id) {
                return $mentor->primary_user_id;
            }

            $currentId = $mentorId;
        }

        return null;
    }
}

