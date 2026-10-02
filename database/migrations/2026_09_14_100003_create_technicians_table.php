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
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Backfill a technician profile for every user who is either a technician by
        // role, or already assigned to a job order today (the assignment dropdown has
        // always allowed admins too — see JobOrderController@index's $technicians
        // query — so an admin who was ever assigned needs a row here as well).
        $now = now();

        $userIds = DB::table('users')->where('role', 'technician')->pluck('id');

        if (Schema::hasColumn('job_orders', 'technician_id')) {
            $userIds = $userIds->merge(
                DB::table('job_orders')->whereNotNull('technician_id')->distinct()->pluck('technician_id')
            );
        }

        $userIds = $userIds->unique()->values();

        if ($userIds->isNotEmpty()) {
            DB::table('technicians')->insert(
                $userIds->map(fn ($userId) => [
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technicians');
    }
};
