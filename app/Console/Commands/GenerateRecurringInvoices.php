<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\RecurringInvoiceSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateRecurringInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-recurring
                            {--schedule= : Generate invoice for a specific schedule ID}
                            {--force : Force generation even if not due}
                            {--dry-run : Show what would be generated without creating invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoices from active recurring invoice schedules';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting recurring invoice generation...');

        $scheduleId = $this->option('schedule');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        try {
            // Get schedules to process
            $schedules = $this->getSchedulesToProcess($scheduleId, $force);

            if ($schedules->isEmpty()) {
                $this->info('No schedules due for invoice generation.');
                return self::SUCCESS;
            }

            $this->info("Found {$schedules->count()} schedule(s) to process.");

            $generated = 0;
            $failed = 0;

            foreach ($schedules as $schedule) {
                try {
                    if ($dryRun) {
                        $this->line("Would generate invoice for schedule: {$schedule->name} (ID: {$schedule->id})");
                        $generated++;
                    } else {
                        $invoice = $this->generateInvoice($schedule);
                        $this->info("✓ Generated invoice {$invoice->invoice_number} for schedule: {$schedule->name}");
                        $generated++;
                    }
                } catch (Exception $e) {
                    $this->error("✗ Failed to generate invoice for schedule: {$schedule->name}");
                    $this->error("  Error: {$e->getMessage()}");
                    Log::error('Recurring invoice generation failed', [
                        'schedule_id' => $schedule->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $failed++;
                }
            }

            $this->newLine();
            $this->info("Summary:");
            $this->info("  Generated: {$generated}");
            if ($failed > 0) {
                $this->error("  Failed: {$failed}");
            }

            return $failed > 0 ? self::FAILURE : self::SUCCESS;

        } catch (Exception $e) {
            $this->error("Fatal error: {$e->getMessage()}");
            Log::error('Recurring invoice generation command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Get schedules that should be processed.
     */
    private function getSchedulesToProcess(?string $scheduleId, bool $force): \Illuminate\Database\Eloquent\Collection
    {
        $query = RecurringInvoiceSchedule::query();

        if ($scheduleId) {
            // Process specific schedule
            $query->where('id', $scheduleId);
        } else {
            // Process all due schedules
            $query->active()->dueForGeneration();
        }

        $schedules = $query->get();

        // Filter out schedules that have reached max occurrences
        return $schedules->reject(function ($schedule) use ($force) {
            if ($schedule->hasReachedMaxOccurrences() && !$force) {
                $this->warn("Schedule '{$schedule->name}' has reached max occurrences. Marking as completed.");
                $schedule->update(['status' => 'completed']);
                return true;
            }

            // Check if end date has passed
            if ($schedule->end_date && $schedule->end_date->isPast() && !$force) {
                $this->warn("Schedule '{$schedule->name}' has passed end date. Marking as completed.");
                $schedule->update(['status' => 'completed']);
                return true;
            }

            return false;
        });
    }

    /**
     * Generate an invoice from a recurring schedule.
     */
    private function generateInvoice(RecurringInvoiceSchedule $schedule): Invoice
    {
        return DB::transaction(function () use ($schedule) {
            // Generate unique invoice number
            $invoiceNumber = $this->generateInvoiceNumber($schedule);

            // Calculate due date
            $issueDate = now();
            $dueDate = $this->calculateDueDate($issueDate, $schedule);

            // Create invoice
            $invoice = Invoice::create([
                'user_id' => $schedule->user_id,
                'organization_id' => $schedule->organization_id,
                'client_id' => $schedule->client_id,
                'invoice_number' => $invoiceNumber,
                'status' => $schedule->auto_send ? 'sent' : 'draft',
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'sent_at' => $schedule->auto_send ? now() : null,
                'subtotal' => $schedule->subtotal,
                'tax_rate' => $schedule->tax_rate,
                'tax_amount' => $schedule->tax_amount,
                'discount_amount' => $schedule->discount_amount,
                'total' => $schedule->total,
                'currency' => $schedule->currency,
                'from_details' => $schedule->from_details,
                'to_details' => $schedule->to_details,
                'line_items' => $schedule->line_items,
                'notes' => $schedule->notes,
                'terms' => $schedule->terms,
                'metadata' => [
                    'recurring_schedule_id' => $schedule->id,
                    'occurrence_number' => $schedule->occurrences_count + 1,
                ],
            ]);

            // Update schedule
            $nextGenerationDate = $schedule->calculateNextGenerationDate();
            $schedule->update([
                'last_generated_date' => now(),
                'next_generation_date' => $nextGenerationDate,
                'occurrences_count' => $schedule->occurrences_count + 1,
            ]);

            // Send notification if enabled
            if ($schedule->notify_on_generation) {
                $this->sendNotification($invoice, $schedule);
            }

            // Auto-send invoice if enabled
            if ($schedule->auto_send) {
                $this->info("  Auto-sending invoice {$invoice->invoice_number}");
                // TODO: Implement email sending
            }

            // Auto-charge if enabled (requires payment gateway connection)
            if ($schedule->auto_charge) {
                $this->info("  Auto-charge enabled for invoice {$invoice->invoice_number}");
                // TODO: Implement auto-charge logic
            }

            return $invoice;
        });
    }

    /**
     * Generate unique invoice number.
     */
    private function generateInvoiceNumber(RecurringInvoiceSchedule $schedule): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $orgId = substr($schedule->organization_id, 0, 8);

        // Find next sequential number for today
        $lastInvoice = Invoice::where('organization_id', $schedule->organization_id)
            ->where('invoice_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract sequence number and increment
            $parts = explode('-', $lastInvoice->invoice_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Calculate invoice due date based on schedule settings.
     */
    private function calculateDueDate($issueDate, RecurringInvoiceSchedule $schedule): \Carbon\Carbon
    {
        $dueDate = \Carbon\Carbon::parse($issueDate);

        switch ($schedule->due_date_type) {
            case 'from_issue':
                $dueDate->addDays($schedule->due_days);
                break;

            case 'from_month_end':
                $dueDate->endOfMonth()->addDays($schedule->due_days);
                break;

            case 'from_month_start':
                $dueDate->startOfMonth()->addDays($schedule->due_days);
                break;
        }

        return $dueDate;
    }

    /**
     * Send notification about generated invoice.
     */
    private function sendNotification(Invoice $invoice, RecurringInvoiceSchedule $schedule): void
    {
        // TODO: Implement notification sending
        $emails = $schedule->notification_emails ?? [];

        Log::info('Recurring invoice generated notification', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'schedule_id' => $schedule->id,
            'notification_emails' => $emails,
        ]);
    }
}
