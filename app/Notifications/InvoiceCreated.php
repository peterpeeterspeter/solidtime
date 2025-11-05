<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCreated extends Notification implements ShouldQueue
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
        $invoiceUrl = url("/invoices/{$this->invoice['id']}");

        return (new MailMessage)
            ->subject("Invoice {$invoiceNumber} from {$this->invoice['organization_name']}")
            ->greeting("Dear {$clientName},")
            ->line("Thank you for your business. Please find your invoice details below:")
            ->line("**Invoice Number:** {$invoiceNumber}")
            ->line("**Amount Due:** {$total}")
            ->line("**Due Date:** {$dueDate}")
            ->line($this->getPaymentTermsLine())
            ->action('View Invoice', $invoiceUrl)
            ->line($this->getBankDetailsSection())
            ->line('If you have any questions about this invoice, please don\'t hesitate to contact us.')
            ->salutation("Best regards,\n{$this->invoice['organization_name']}");
    }

    /**
     * Get the payment terms line.
     */
    protected function getPaymentTermsLine(): string
    {
        $terms = $this->invoice['payment_terms'] ?? 'Net 30 days';
        return "**Payment Terms:** {$terms}";
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
            'type' => 'invoice_created',
        ];
    }
}
