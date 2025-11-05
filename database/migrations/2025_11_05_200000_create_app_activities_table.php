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
        Schema::create('app_activities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index();
            $table->foreignUuid('organization_id')->index();

            // Activity data
            $table->string('app_name')->index();
            $table->text('encrypted_data'); // Encrypted JSON containing window_title and other sensitive data
            $table->integer('active_seconds')->default(0);
            $table->integer('idle_seconds')->default(0);
            $table->integer('keyboard_count')->default(0);
            $table->integer('mouse_count')->default(0);

            // Metadata
            $table->timestamp('recorded_at')->index();
            $table->string('client_version')->nullable();
            $table->string('platform', 20)->nullable(); // windows, macos, linux

            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'recorded_at']);
            $table->index(['organization_id', 'recorded_at']);
            $table->index(['user_id', 'app_name', 'recorded_at']);

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_activities');
    }
};
