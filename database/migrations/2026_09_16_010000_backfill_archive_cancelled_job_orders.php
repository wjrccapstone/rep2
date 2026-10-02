<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One-time backfill: cancelled job orders that existed before auto-archive-on-
     * cancel shipped never triggered that hook, so they'd otherwise sit in the
     * active list forever. This moves them into the Archive tab immediately.
     */
    public function up(): void
    {
        DB::table('job_orders')
            ->where('status', 'cancelled')
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible — there's no record of which cancelled rows this backfill
        // archived versus ones archived some other way before or after it ran.
    }
};
