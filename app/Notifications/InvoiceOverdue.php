<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public array $invoice
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $invoiceNumber = $this->invoice['invoice_number'];
        $clientName = $this->invoice['client_name'] ?? 'Valued Client';
        $total = $this->formatCurrency($this->invoice['total'], $this->invoice['currency'] ?? 'EUR');
        $dueDate = $this->formatDate($this->invoice['due_date']);
        $daysOverdue = $this->calculateDaysOverdue($this->invoice['due_date']);
        $invoiceUrl = url("/invoices/{$this->invoice['id']}");

        $message = (new MailMessage)
            ->subject("⚠️ Payment Reminder: Invoice {$invoiceNumber} is Overdue")
            ->greeting("Dear {$clientName},")
            ->line("This is a friendly reminder that the following invoice is now overdue:")
            ->line("**Invoice Number:** {$invoiceNumber}")
            ->line("**Amount Due:** {$total}")
            ->line("**Original Due Date:** {$dueDate}")
            ->line("**Days Overdue:** {$daysOverdue} days");

        if ($daysOverdue > 30) {
            $message->line('⚠️ **This invoice is significantly overdue.** Please prioritize payment to avoid any service interruptions.');
        } else {
            $message->line('We kindly request that you process this payment at your earliest convenience.');
        }

        $message->action('View & Pay Invoice', $invoiceUrl)
            ->line($this->getBankDetailsSection())
            ->line('If payment has already been made, please disregard this reminder.')
            ->line('If you have any questions or concerns about this invoice, please contact us immediately.')
            ->salutation("Best regards,\n{$this->invoice['organization_name']}");

        return $message;
    }

    /**
     * Calculate days overdue.
     */
    protected function calculateDaysOverdue(?string $dueDate): int
    {
        if (! $dueDate) {
            return 0;
        }

        $due = strtotime($dueDate);
        $now = time();
        $diff = $now - $due;

        return max(0, (int) floor($diff / 86400)); // 86400 seconds in a day
    }

    /**
     * Get the bank details section.
     */
    protected function getBankDetailsSection(): string
    {
        if (empty($this->invoice['bank_details'])) {
            return '';
        }

        $details = $this->invoice['bank_details'];
        $lines = ['**Bank Transfer Details:**'];

        if (isset($details['bank_name'])) {
            $lines[] = "Bank: {$details['bank_name']}";
        }
        if (isset($details['iban'])) {
            $lines[] = "IBAN: {$details['iban']}";
        }
        if (isset($details['bic'])) {
            $lines[] = "BIC/SWIFT: {$details['bic']}";
        }

        return implode("\n", $lines);
    }

    /**
     * Format currency amount.
     */
    protected function formatCurrency(float $amount, string $currency): string
    {
        return number_format($amount, 2, '.', ',') . ' ' . $currency;
    }

    /**
     * Format date.
     */
    protected function formatDate(?string $date): string
    {
        if (! $date) {
            return 'N/A';
        }

        return date('F j, Y', strtotime($date));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice['id'],
            'invoice_number' => $this->invoice['invoice_number'],
            'total' => $this->invoice['total'],
            'days_overdue' => $this->calculateDaysOverdue($this->invoice['due_date']),
            'type' => 'invoice_overdue',
        ];
    }
}
