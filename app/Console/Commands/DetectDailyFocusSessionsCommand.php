<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\User;
use App\Services\ActivityAggregationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DetectDailyFocusSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'focus-sessions:detect-daily
                            {--date= : Date to process (default: yesterday)}
                            {--user= : Process only for specific user ID}
                            {--dry-run : Display what would be processed without actually processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and persist focus sessions from activity data for all users';

    /**
     * Execute the console command.
     */
    public function handle(ActivityAggregationService $aggregationService): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        $this->info("Processing focus sessions for {$date->toDateString()}...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No sessions will be saved');
        }

        // Get users to process
        $usersQuery = User::query();
        if ($userId) {
            $usersQuery->where('id', $userId);
        }

        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            $this->warn('No users found to process.');

            return self::SUCCESS;
        }

        $this->info("Processing {$users->count()} user(s)...");

        $totalSessions = 0;
        $processedUsers = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            try {
                // Get user's organization
                $membership = Member::where('user_id', $user->id)->first();

                if (! $membership) {
                    $this->newLine();
                    $this->warn("  Skipping user {$user->id} - no organization membership");
                    $progressBar->advance();
                    continue;
                }

                if ($dryRun) {
                    // Just detect, don't persist
                    $detectedSessions = $aggregationService->detectFocusSessions($user->id, $date);
                    $count = count($detectedSessions);
                } else {
                    // Detect and persist
                    $sessions = $aggregationService->detectAndPersistFocusSessions(
                        $user->id,
                        $membership->organization_id,
                        $date
                    );
                    $count = $sessions->count();
                }

                $totalSessions += $count;
                $processedUsers++;

                if ($count > 0) {
                    $this->newLine();
                    $this->line("  User {$user->name} ({$user->id}): {$count} session(s)");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("  Error processing user {$user->id}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Processing complete!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Users processed', $processedUsers],
                ['Focus sessions detected', $totalSessions],
                ['Errors', $errors],
                ['Date', $date->toDateString()],
                ['Mode', $dryRun ? 'DRY RUN' : 'LIVE'],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to persist sessions.');
        }

        return self::SUCCESS;
    }
}
