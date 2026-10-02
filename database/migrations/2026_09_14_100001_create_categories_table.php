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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Seed the canonical list products have always been restricted to
        // (Product::CATEGORIES / the category <select> options).
        $now = now();
        DB::table('categories')->insert(collect([
            'Laptops', 'Components', 'Peripherals', 'Networking', 'Storage', 'Accessories',
        ])->map(fn ($name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now])->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
