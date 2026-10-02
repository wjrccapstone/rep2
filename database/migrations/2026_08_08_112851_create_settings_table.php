<?php

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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('two_factor_enabled')->default(true);
            $table->unsignedInteger('session_timeout_minutes')->default(20);
            $table->unsignedInteger('password_expiry_days')->default(30);
            $table->boolean('email_notifications')->default(true);
            $table->boolean('job_status_updates')->default(true);
            $table->boolean('payment_reminders')->default(true);
            $table->string('backup_frequency')->default('daily');
            $table->string('data_retention_period')->default('6_months');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
