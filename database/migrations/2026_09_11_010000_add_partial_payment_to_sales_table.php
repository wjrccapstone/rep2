<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Partial payment support. When a sale is settled in full, amount_paid == total
     * and balance_due == 0. When the customer pays only part of it ("Unpaid — partial /
     * on account"), amount_paid holds what they handed over now and balance_due is the
     * remainder still owed (total − amount_paid).
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)->default(0)->after('change_due');
            $table->decimal('balance_due', 10, 2)->default(0)->after('amount_paid');
        });

        // Backfill existing rows: everything already recorded was treated as fully paid.
        Schema::hasColumn('sales', 'total') && \Illuminate\Support\Facades\DB::statement(
            'UPDATE sales SET amount_paid = total, balance_due = 0'
        );
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'balance_due']);
        });
    }
};
