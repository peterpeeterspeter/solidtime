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
        Schema::create('focus_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->index()->constrained()->cascadeOnDelete();

            $table->timestamp('start_time')->index();
            $table->timestamp('end_time')->index();
            $table->integer('duration_minutes');
            $table->integer('app_switches')->default(0);
            $table->integer('unique_apps_count')->default(1);
            $table->integer('interruptions_count')->default(0);
            $table->integer('focus_score')->default(0); // 0-100
            $table->json('apps_used'); // Array of app names
            $table->string('primary_app')->nullable();

            $table->timestamps();

            // Composite indexes for queries
            $table->index(['user_id', 'start_time']);
            $table->index(['organization_id', 'start_time']);
            $table->index(['user_id', 'focus_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('focus_sessions');
    }
};
