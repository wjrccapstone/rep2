<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('service')->constrained()->restrictOnDelete();
        });

        DB::statement(
            'UPDATE job_orders INNER JOIN services ON services.name = job_orders.service '.
            'SET job_orders.service_id = services.id'
        );

        Schema::table('job_orders', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable(false)->change();
            $table->dropColumn('service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->string('service')->nullable()->after('technician_id');
        });

        DB::statement(
            'UPDATE job_orders INNER JOIN services ON services.id = job_orders.service_id '.
            'SET job_orders.service = services.name'
        );

        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
        });
    }
};
