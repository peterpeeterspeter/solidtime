<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('organization_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('payment_gateway_connection_id')->nullable()->constrained()->onDelete('set null');

            // Payment gateway info
            $table->enum('gateway', ['stripe', 'paypal', 'bank_transfer', 'cash', 'check', 'other'])->index();
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->string('gateway_payment_method_id')->nullable(); // e.g., Stripe payment method ID

            // Amount details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('fee_amount', 10, 2)->default(0); // Gateway fees
            $table->decimal('net_amount', 10, 2); // Amount after fees

            // Status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'partially_refunded'])->default('pending')->index();
            $table->text('status_message')->nullable(); // Error messages, notes

            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Refund details
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->text('refund_reason')->nullable();
            $table->string('refund_transaction_id')->nullable();

            // Customer details (cached from gateway)
            $table->json('customer_details')->nullable(); // Name, email, billing address

            // Raw gateway response (for debugging/reconciliation)
            $table->json('gateway_response')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index(['invoice_id']);
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
