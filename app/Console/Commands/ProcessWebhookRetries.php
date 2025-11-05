<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WebhookDispatcher;
use Illuminate\Console\Command;

class ProcessWebhookRetries extends Command
{
    protected $signature = 'webhooks:process-retries
                            {--limit=100 : Maximum number of retries to process}';

    protected $description = 'Process failed webhook deliveries that are ready for retry';

    public function __construct(
        protected WebhookDispatcher $dispatcher
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Processing webhook retries...');

        $processed = $this->dispatcher->processRetries();

        $this->info("Processed {$processed} webhook retries");

        return self::SUCCESS;
    }
}
