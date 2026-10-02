<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * job_orders.technician_id has pointed straight at users.id since the table was
     * created. Now that technicians is its own entity, repoint it at technicians.id
     * instead — assignment stays a user pick in the UI (JobOrderController resolves
     * the chosen user to a technician profile before saving), this only changes what
     * the foreign key on job_orders means.
     */
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->foreignId('technician_ref_id')->nullable()->after('technician_id')
                ->constrained('technicians')->nullOnDelete();
        });

        DB::statement(
            'UPDATE job_orders INNER JOIN technicians ON technicians.user_id = job_orders.technician_id '.
            'SET job_orders.technician_ref_id = technicians.id '.
            'WHERE job_orders.technician_id IS NOT NULL'
        );

        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropForeign(['technician_id']);
            $table->dropColumn('technician_id');
        });

        Schema::table('job_orders', function (Blueprint $table) {
            $table->renameColumn('technician_ref_id', 'technician_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->renameColumn('technician_id', 'technician_ref_id');
        });

        Schema::table('job_orders', function (Blueprint $table) {
            $table->foreignId('technician_id')->nullable()->after('customer_id')
                ->constrained('users')->nullOnDelete();
        });

        DB::statement(
            'UPDATE job_orders INNER JOIN technicians ON technicians.id = job_orders.technician_ref_id '.
            'SET job_orders.technician_id = technicians.user_id '.
            'WHERE job_orders.technician_ref_id IS NOT NULL'
        );

        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('technician_ref_id');
        });
    }
};
