<?php

declare(strict_types=1);

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
        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('webhook_id')->index();
            $table->string('event_type', 100); // e.g., 'time_entry.created'
            $table->json('payload'); // The data sent to webhook
            $table->smallInteger('attempt')->default(1); // Retry attempt number (1-5)
            $table->integer('response_status')->nullable(); // HTTP status code (200, 404, 500, etc.)
            $table->text('response_body')->nullable(); // Response from webhook endpoint
            $table->text('error_message')->nullable(); // Error details if delivery failed
            $table->timestamp('delivered_at')->nullable(); // When delivery was attempted
            $table->timestamp('created_at')->useCurrent(); // When delivery was queued

            // Foreign key constraint
            $table->foreign('webhook_id')
                ->references('id')
                ->on('webhooks')
                ->onDelete('cascade');

            // Indexes for querying delivery history
            $table->index(['webhook_id', 'created_at']);
            $table->index(['webhook_id', 'event_type']);
            $table->index('created_at'); // For cleanup jobs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
