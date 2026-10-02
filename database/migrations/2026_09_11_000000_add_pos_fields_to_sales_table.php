<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cashier point-of-sale fields. A "walk-in product sale" keeps job_order_id null
     * and labor_cost 0; a "job order billing" sale links the job order and carries its
     * service cost as labor_cost. The tax breakdown mirrors a BIR-style receipt:
     * prices are VAT-inclusive, so vatable_sales + vat_amount == the pre-discount net,
     * and a Senior/PWD sale is VAT-exempt with a 20% discount on the VAT-stripped price.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('sale_type')->default('walk_in')->after('code');
            $table->foreignId('job_order_id')->nullable()->after('sale_type')->constrained()->nullOnDelete();

            $table->decimal('gross', 10, 2)->default(0)->after('total');
            $table->decimal('labor_cost', 10, 2)->default(0)->after('gross');
            $table->decimal('vatable_sales', 10, 2)->default(0)->after('labor_cost');
            $table->decimal('vat_amount', 10, 2)->default(0)->after('vatable_sales');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('vat_amount');
            $table->boolean('senior_pwd')->default(false)->after('discount_amount');

            $table->decimal('amount_tendered', 10, 2)->nullable()->after('senior_pwd');
            $table->decimal('change_due', 10, 2)->default(0)->after('amount_tendered');
            $table->boolean('is_paid')->default(true)->after('change_due');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_order_id');
            $table->dropColumn([
                'sale_type', 'gross', 'labor_cost', 'vatable_sales', 'vat_amount',
                'discount_amount', 'senior_pwd', 'amount_tendered', 'change_due', 'is_paid',
            ]);
        });
    }
};
