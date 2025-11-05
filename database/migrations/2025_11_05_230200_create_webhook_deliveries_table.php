<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('webhook_id')->index()->constrained()->cascadeOnDelete();

            // Delivery details
            $table->string('event_type')->index()->comment('Type of event triggered');
            $table->json('payload')->comment('Full event payload sent');
            $table->string('delivery_id')->unique()->comment('Unique delivery attempt ID');

            // Response tracking
            $table->enum('status', ['pending', 'success', 'failed', 'retrying'])->index();
            $table->integer('http_status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();

            // Timing
            $table->timestamp('attempted_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable()->comment('Request duration in milliseconds');

            // Retry logic
            $table->integer('attempt_number')->default(1);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('next_retry_at')->nullable()->index();

            // Metadata
            $table->timestamps();

            // Composite indexes
            $table->index(['webhook_id', 'status']);
            $table->index(['event_type', 'attempted_at']);
            $table->index(['status', 'next_retry_at']); // For retry queue
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
