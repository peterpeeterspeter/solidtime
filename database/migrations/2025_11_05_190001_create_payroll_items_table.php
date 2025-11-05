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
        Schema::create('payroll_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('payroll_id')->index();
            $table->foreignUuid('member_id')->index();
            $table->foreignUuid('user_id')->index();
            $table->decimal('regular_hours', 10, 2)->default(0);
            $table->decimal('overtime_hours', 10, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('overtime_rate', 10, 2); // Calculated as hourly_rate * multiplier
            $table->decimal('regular_earnings', 12, 2)->default(0);
            $table->decimal('overtime_earnings', 12, 2)->default(0);
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->json('time_entry_ids')->nullable(); // Array of time entry IDs included
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('payroll_id')
                ->references('id')
                ->on('payrolls')
                ->onDelete('cascade');

            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->index(['payroll_id', 'member_id']);
            $table->index(['payroll_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
