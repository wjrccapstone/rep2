<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin_notices', function (Blueprint $table) {
            $table->id();
            $table->string('tab', 20);                 // job_orders, products, parts_out
            $table->string('title', 120);
            $table->text('message');
            $table->string('reference', 60)->nullable(); // e.g. JON-882, PI-016
            $table->boolean('is_important')->default(false);
            $table->date('remove_on')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tab', 'remove_on']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('bulletin_seen_at')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('bulletin_seen_at'));
        Schema::dropIfExists('bulletin_notices');
    }
};