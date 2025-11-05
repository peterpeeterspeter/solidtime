<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AppActivity;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupOldActivitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activities:cleanup
                            {--days=90 : Number of days to retain activity data}
                            {--dry-run : Display what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete activity snapshots older than specified retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up activities older than {$days} days (before {$cutoffDate->toDateString()})...");

        $query = AppActivity::where('recorded_at', '<', $cutoffDate);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No activities found to delete.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("DRY RUN: Would delete {$count} activity snapshots.");

            // Show breakdown by user
            $this->info('Breakdown by user:');
            $breakdown = AppActivity::where('recorded_at', '<', $cutoffDate)
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->get();

            foreach ($breakdown as $item) {
                $this->line("  User {$item->user_id}: {$item->count} snapshots");
            }

            return self::SUCCESS;
        }

        if (! $this->confirm("Are you sure you want to delete {$count} activity snapshots?")) {
            $this->info('Cleanup cancelled.');

            return self::SUCCESS;
        }

        // Delete in chunks to avoid memory issues
        $deleted = 0;
        $chunkSize = 1000;

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        AppActivity::where('recorded_at', '<', $cutoffDate)
            ->chunkById($chunkSize, function ($activities) use (&$deleted, $progressBar): void {
                foreach ($activities as $activity) {
                    $activity->delete();
                    $deleted++;
                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Successfully deleted {$deleted} activity snapshots.");

        return self::SUCCESS;
    }
}
