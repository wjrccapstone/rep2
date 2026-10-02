<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('module'); // product_inventory | point_of_sale | job_orders | user_accounts
            $table->string('action'); // added, price_changed, stock_adjusted, archived, restored, deleted, created, updated, status_changed, password_reset, sale_recorded, imported
            $table->string('reference')->nullable(); // e.g. PI-019, JON-045, TXN-041, USR_004
            $table->string('reference_route')->nullable(); // named route to link the reference to, if any
            $table->unsignedBigInteger('reference_id')->nullable(); // route-model-binding id for reference_route
            $table->string('title'); // main "what changed" line, e.g. the product/job/user name
            $table->string('detail')->nullable(); // secondary note, e.g. "Category moved to Accessories"
            $table->string('before_value')->nullable();
            $table->string('after_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['module', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
