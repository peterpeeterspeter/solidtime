<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->index()->constrained()->cascadeOnDelete();

            // API Key details
            $table->string('name')->comment('User-friendly name for the key');
            $table->string('key_prefix', 20)->unique()->comment('First 8 chars for identification');
            $table->string('key_hash')->unique()->comment('Hashed full key for verification');
            $table->text('description')->nullable()->comment('Purpose of this API key');

            // Permissions and scopes
            $table->json('scopes')->comment('Array of permitted scopes/permissions');
            $table->boolean('is_active')->default(true)->index();

            // Usage tracking
            $table->timestamp('last_used_at')->nullable()->index();
            $table->string('last_used_ip')->nullable();
            $table->bigInteger('usage_count')->default(0);

            // Expiration
            $table->timestamp('expires_at')->nullable()->index();

            // Metadata
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for performance
            $table->index(['organization_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
