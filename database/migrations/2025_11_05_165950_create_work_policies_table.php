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
        Schema::create('work_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->unique()->index();
            $table->decimal('max_daily_hours', 5, 2)->nullable()->comment('Maximum hours per day (e.g., 10.00)');
            $table->decimal('min_daily_hours', 5, 2)->nullable()->comment('Minimum hours per day (e.g., 4.00)');
            $table->integer('required_break_duration_minutes')->nullable()->comment('Required break duration in minutes');
            $table->decimal('overtime_threshold_hours', 5, 2)->nullable()->comment('Hours before overtime kicks in (e.g., 8.00)');
            $table->boolean('enforce_max_hours')->default(false)->comment('Block time entries exceeding max hours');
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_policies');
    }
};
