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
        Schema::create('payrolls', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->index();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 20)->default('draft'); // draft, approved, paid
            $table->decimal('total_regular_hours', 10, 2)->default(0);
            $table->decimal('total_overtime_hours', 10, 2)->default(0);
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('approved_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for querying
            $table->index(['organization_id', 'period_start', 'period_end']);
            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
