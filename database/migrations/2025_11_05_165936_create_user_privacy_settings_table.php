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
        Schema::create('user_privacy_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->index();
            $table->smallInteger('tracking_level')->default(0)->comment('0: Manual, 1: Idle Detection, 2: Monitoring, 3: Full Tracking');
            $table->boolean('screenshot_enabled')->default(false);
            $table->boolean('app_tracking_enabled')->default(false);
            $table->boolean('url_tracking_enabled')->default(false);
            $table->boolean('keyboard_mouse_tracking_enabled')->default(false);
            $table->boolean('geolocation_enabled')->default(false);
            $table->integer('data_retention_days')->default(90);
            $table->boolean('encryption_enabled')->default(true);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_privacy_settings');
    }
};
