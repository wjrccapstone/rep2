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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('cashier')->change();
        });

        DB::table('users')->where('role', 'manager')->update(['role' => 'cashier']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'cashier')->update(['role' => 'manager']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('manager')->change();
        });
    }
};
