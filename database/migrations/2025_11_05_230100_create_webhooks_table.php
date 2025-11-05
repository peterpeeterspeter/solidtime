<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->index()->constrained()->cascadeOnDelete();

            // Webhook details
            $table->string('name')->comment('User-friendly name');
            $table->text('description')->nullable();
            $table->string('url')->comment('Webhook endpoint URL');
            $table->string('secret')->nullable()->comment('Webhook signing secret');

            // Event configuration
            $table->json('events')->comment('Array of subscribed event types');
            $table->json('filters')->nullable()->comment('Optional event filters');

            // Status and health
            $table->boolean('is_active')->default(true)->index();
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('last_error')->nullable();

            // Security
            $table->enum('verification_status', ['pending', 'verified', 'failed'])->default('pending');
            $table->timestamp('verified_at')->nullable();

            // Metadata
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes
            $table->index(['organization_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
