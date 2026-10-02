<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['vatable_sales', 'vat_amount', 'discount_amount', 'senior_pwd']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('vatable_sales', 10, 2)->default(0)->after('labor_cost');
            $table->decimal('vat_amount', 10, 2)->default(0)->after('vatable_sales');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('vat_amount');
            $table->boolean('senior_pwd')->default(false)->after('discount_amount');
        });
    }
};
