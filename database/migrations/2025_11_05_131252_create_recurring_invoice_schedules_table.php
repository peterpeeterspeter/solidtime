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
        Schema::create('recurring_invoice_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('organization_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('client_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('project_id')->nullable()->constrained()->onDelete('set null');

            // Schedule configuration
            $table->string('name'); // e.g., "Monthly Retainer - Client X"
            $table->enum('frequency', ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'biannually', 'annually'])->index();
            $table->integer('interval')->default(1); // e.g., every 2 months
            $table->integer('day_of_month')->nullable(); // For monthly: which day to generate (1-31)
            $table->integer('day_of_week')->nullable(); // For weekly: 0=Sunday, 6=Saturday

            // Schedule timing
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = indefinite
            $table->date('next_generation_date')->index();
            $table->date('last_generated_date')->nullable();

            // Limits
            $table->integer('max_occurrences')->nullable(); // null = unlimited
            $table->integer('occurrences_count')->default(0);

            // Status
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active')->index();

            // Invoice template data
            $table->json('from_details'); // Company info template
            $table->json('to_details'); // Client info template
            $table->json('line_items'); // Default line items
            $table->text('notes')->nullable(); // Default notes
            $table->text('terms')->nullable(); // Default terms

            // Amounts
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Due date calculation
            $table->integer('due_days')->default(30); // Days after generation
            $table->enum('due_date_type', ['from_issue', 'from_month_end', 'from_month_start'])->default('from_issue');

            // Auto-send settings
            $table->boolean('auto_send')->default(false);
            $table->boolean('auto_charge')->default(false); // Auto-charge via payment gateway

            // Time entry inclusion
            $table->boolean('include_time_entries')->default(false); // Auto-include unbilled time
            $table->date('time_entries_from_date')->nullable();
            $table->date('time_entries_to_date')->nullable();

            // Notifications
            $table->boolean('notify_on_generation')->default(true);
            $table->json('notification_emails')->nullable(); // Additional emails to notify

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index(['client_id']);
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_invoice_schedules');
    }
};
