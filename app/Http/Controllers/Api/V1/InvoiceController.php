<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Invoice\InvoiceIndexRequest;
use App\Http\Requests\V1\Invoice\InvoiceStoreRequest;
use App\Http\Requests\V1\Invoice\InvoiceUpdateRequest;
use App\Http\Resources\V1\Invoice\InvoiceCollection;
use App\Http\Resources\V1\Invoice\InvoiceResource;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Invoice $invoice = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($invoice !== null && $invoice->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Invoice does not belong to organization');
        }
    }

    /**
     * Get invoices
     *
     * @return InvoiceCollection<InvoiceResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getInvoices
     */
    public function index(Organization $organization, InvoiceIndexRequest $request): InvoiceCollection
    {
        $this->checkPermission($organization, 'invoices:view');

        $invoicesQuery = Invoice::query()
            ->whereBelongsTo($organization, 'organization')
            ->with(['client', 'payments'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $invoicesQuery->byStatus($status);
            }
        }

        // Filter by client
        if ($clientId = $request->input('client_id')) {
            $invoicesQuery->where('client_id', $clientId);
        }

        // Search by invoice number or client name
        if ($search = $request->input('search')) {
            $invoicesQuery->where(function ($query) use ($search) {
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Date range filters
        if ($startDate = $request->input('start_date')) {
            $invoicesQuery->where('issue_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $invoicesQuery->where('issue_date', '<=', $endDate);
        }

        $invoices = $invoicesQuery->paginate(config('app.pagination_per_page_default'));

        return new InvoiceCollection($invoices);
    }

    /**
     * Get a single invoice
     *
     * @throws AuthorizationException
     *
     * @operationId getInvoice
     */
    public function show(Organization $organization, Invoice $invoice): InvoiceResource
    {
        $this->checkPermission($organization, 'invoices:view', $invoice);

        $invoice->load(['client', 'payments']);

        return new InvoiceResource($invoice);
    }

    /**
     * Create invoice
     *
     * @throws AuthorizationException
     *
     * @operationId createInvoice
     */
    public function store(Organization $organization, InvoiceStoreRequest $request): InvoiceResource
    {
        $this->checkPermission($organization, 'invoices:create');

        $user = $this->user();

        // Generate invoice number if not provided
        $invoiceNumber = $request->input('invoice_number');
        if (!$invoiceNumber) {
            $invoiceNumber = $this->generateInvoiceNumber($organization);
        }

        $invoice = new Invoice;
        $invoice->user_id = $user->id;
        $invoice->organization_id = $organization->id;
        $invoice->client_id = $request->input('client_id');
        $invoice->invoice_number = $invoiceNumber;
        $invoice->status = $request->input('status', 'draft');
        $invoice->issue_date = $request->input('issue_date');
        $invoice->due_date = $request->input('due_date');
        $invoice->from_details = $request->input('from_details');
        $invoice->to_details = $request->input('to_details');
        $invoice->line_items = $request->input('line_items');
        $invoice->subtotal = $request->input('subtotal');
        $invoice->tax_rate = $request->input('tax_rate', 0);
        $invoice->tax_amount = $request->input('tax_amount', 0);
        $invoice->discount_amount = $request->input('discount_amount', 0);
        $invoice->total = $request->input('total');
        $invoice->currency = $request->input('currency', 'USD');
        $invoice->notes = $request->input('notes');
        $invoice->terms = $request->input('terms');
        $invoice->payment_method = $request->input('payment_method');
        $invoice->payment_instructions = $request->input('payment_instructions');
        $invoice->metadata = $request->input('metadata', []);
        $invoice->save();

        return new InvoiceResource($invoice);
    }

    /**
     * Update invoice
     *
     * @throws AuthorizationException
     *
     * @operationId updateInvoice
     */
    public function update(Organization $organization, Invoice $invoice, InvoiceUpdateRequest $request): InvoiceResource
    {
        $this->checkPermission($organization, 'invoices:update', $invoice);

        // Only allow updating draft invoices
        if (!$invoice->isDraft() && !$request->has('status')) {
            throw new AuthorizationException('Can only update draft invoices');
        }

        if ($request->has('client_id')) {
            $invoice->client_id = $request->input('client_id');
        }
        if ($request->has('invoice_number')) {
            $invoice->invoice_number = $request->input('invoice_number');
        }
        if ($request->has('status')) {
            $invoice->status = $request->input('status');
        }
        if ($request->has('issue_date')) {
            $invoice->issue_date = $request->input('issue_date');
        }
        if ($request->has('due_date')) {
            $invoice->due_date = $request->input('due_date');
        }
        if ($request->has('from_details')) {
            $invoice->from_details = $request->input('from_details');
        }
        if ($request->has('to_details')) {
            $invoice->to_details = $request->input('to_details');
        }
        if ($request->has('line_items')) {
            $invoice->line_items = $request->input('line_items');
        }
        if ($request->has('subtotal')) {
            $invoice->subtotal = $request->input('subtotal');
        }
        if ($request->has('tax_rate')) {
            $invoice->tax_rate = $request->input('tax_rate');
        }
        if ($request->has('tax_amount')) {
            $invoice->tax_amount = $request->input('tax_amount');
        }
        if ($request->has('discount_amount')) {
            $invoice->discount_amount = $request->input('discount_amount');
        }
        if ($request->has('total')) {
            $invoice->total = $request->input('total');
        }
        if ($request->has('currency')) {
            $invoice->currency = $request->input('currency');
        }
        if ($request->has('notes')) {
            $invoice->notes = $request->input('notes');
        }
        if ($request->has('terms')) {
            $invoice->terms = $request->input('terms');
        }
        if ($request->has('payment_method')) {
            $invoice->payment_method = $request->input('payment_method');
        }
        if ($request->has('payment_instructions')) {
            $invoice->payment_instructions = $request->input('payment_instructions');
        }
        if ($request->has('metadata')) {
            $invoice->metadata = $request->input('metadata');
        }

        $invoice->save();

        return new InvoiceResource($invoice);
    }

    /**
     * Delete invoice
     *
     * @throws AuthorizationException
     *
     * @operationId deleteInvoice
     */
    public function destroy(Organization $organization, Invoice $invoice): JsonResponse
    {
        $this->checkPermission($organization, 'invoices:delete', $invoice);

        // Only allow deleting draft invoices
        if (!$invoice->isDraft()) {
            throw new AuthorizationException('Can only delete draft invoices');
        }

        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully',
        ]);
    }

    /**
     * Mark invoice as sent
     *
     * @throws AuthorizationException
     *
     * @operationId markInvoiceAsSent
     */
    public function markAsSent(Organization $organization, Invoice $invoice): InvoiceResource
    {
        $this->checkPermission($organization, 'invoices:update', $invoice);

        $invoice->markAsSent();

        return new InvoiceResource($invoice);
    }

    /**
     * Mark invoice as paid
     *
     * @throws AuthorizationException
     *
     * @operationId markInvoiceAsPaid
     */
    public function markAsPaid(Organization $organization, Invoice $invoice): InvoiceResource
    {
        $this->checkPermission($organization, 'invoices:update', $invoice);

        $invoice->markAsPaid();

        return new InvoiceResource($invoice);
    }

    /**
     * Generate a unique invoice number for the organization
     */
    private function generateInvoiceNumber(Organization $organization): string
    {
        $year = now()->year;
        $month = now()->format('m');

        // Get the latest invoice for this organization
        $latestInvoice = Invoice::query()
            ->whereBelongsTo($organization, 'organization')
            ->where('invoice_number', 'like', "INV-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($latestInvoice) {
            // Extract the sequence number and increment
            $parts = explode('-', $latestInvoice->invoice_number);
            $sequence = intval(end($parts)) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('INV-%s%s-%04d', $year, $month, $sequence);
    }
}
