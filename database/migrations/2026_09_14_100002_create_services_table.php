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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        // Seed the canonical list the "Service Type" <select> has always offered
        // (JobOrder::SERVICE_TYPES).
        DB::table('services')->insert(collect([
            'Computer Repair', 'Software Setup', 'Hardware Installation',
            'Virus Removal', 'Consultation', 'Maintenance',
        ])->map(fn ($name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now])->all());

        // The form has always allowed a free-text fallback value alongside those
        // options (see setSelectWithFallback() in job-orders/index.blade.php), so
        // existing job_orders.service values aren't guaranteed to be one of the
        // six above. Carry forward any distinct value found in the data too, so
        // no historical job order loses its service on the switch to service_id.
        if (Schema::hasColumn('job_orders', 'service')) {
            $existing = DB::table('services')->pluck('name')->all();

            $extra = DB::table('job_orders')
                ->whereNotNull('service')
                ->where('service', '!=', '')
                ->distinct()
                ->pluck('service')
                ->reject(fn ($name) => in_array($name, $existing, true));

            if ($extra->isNotEmpty()) {
                DB::table('services')->insert(
                    $extra->map(fn ($name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now])->all()
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
