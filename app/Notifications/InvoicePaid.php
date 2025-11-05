<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaid extends Notification implements ShouldQueue
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
        $paidDate = $this->formatDate($this->invoice['paid_date'] ?? date('Y-m-d'));
        $invoiceUrl = url("/invoices/{$this->invoice['id']}");

        return (new MailMessage)
            ->subject("✅ Payment Received: Invoice {$invoiceNumber}")
            ->greeting("Dear {$clientName},")
            ->line("Thank you! We have received your payment for the following invoice:")
            ->line("**Invoice Number:** {$invoiceNumber}")
            ->line("**Amount Paid:** {$total}")
            ->line("**Payment Date:** {$paidDate}")
            ->line('Your payment has been successfully processed and applied to your account.')
            ->action('View Invoice Receipt', $invoiceUrl)
            ->line($this->getReceiptNote())
            ->line('We appreciate your business and look forward to working with you again!')
            ->salutation("Best regards,\n{$this->invoice['organization_name']}");
    }

    /**
     * Get receipt note.
     */
    protected function getReceiptNote(): string
    {
        return '📄 A copy of this invoice has been marked as paid in our system. You can download the receipt at any time from the link above.';
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
            'type' => 'invoice_paid',
        ];
    }
}
