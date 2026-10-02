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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->unique()->constrained()->cascadeOnDelete();
            // Free-text job_orders.device could never be reliably split into these three,
            // so brand/type are left blank on backfilled rows rather than guessed at.
            $table->string('device_brand')->nullable();
            $table->string('device_type')->nullable();
            $table->string('device_model');
            $table->timestamps();
        });

        if (Schema::hasColumn('job_orders', 'device')) {
            $now = now();

            DB::table('job_orders')->select('id', 'device')->orderBy('id')
                ->chunkById(200, function ($jobOrders) use ($now) {
                    DB::table('devices')->insert($jobOrders->map(fn ($jobOrder) => [
                        'job_order_id' => $jobOrder->id,
                        'device_model' => $jobOrder->device !== null && $jobOrder->device !== ''
                            ? $jobOrder->device
                            : 'Unspecified',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all());
                });

            Schema::table('job_orders', function (Blueprint $table) {
                $table->dropColumn('device');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('job_orders', 'device')) {
            Schema::table('job_orders', function (Blueprint $table) {
                $table->string('device')->nullable()->after('service');
            });

            DB::table('devices')->orderBy('id')->chunkById(200, function ($devices) {
                foreach ($devices as $device) {
                    DB::table('job_orders')->where('id', $device->job_order_id)->update([
                        'device' => $device->device_model,
                    ]);
                }
            });
        }

        Schema::dropIfExists('devices');
    }
};
