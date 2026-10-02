<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Unpaid" is no longer a valid payment_status — every job order starts "partial"
     * and becomes "paid" once fully settled.
     */
    public function up(): void
    {
        DB::table('job_orders')->where('payment_status', 'unpaid')->update(['payment_status' => 'partial']);

        Schema::table('job_orders', function (Blueprint $table) {
            $table->string('payment_status')->default('partial')->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->change();
        });
    }
};
