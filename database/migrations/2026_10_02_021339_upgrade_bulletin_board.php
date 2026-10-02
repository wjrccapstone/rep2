<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletin_notices', function (Blueprint $table) {
            $table->string('priority', 20)->default('info')->after('message');        // urgent | important | info
            $table->string('category', 40)->default('announcement')->after('priority');
            $table->string('audience', 20)->default('all')->after('category');        // all | admin | cashier | technician
            $table->boolean('is_pinned')->default(false)->after('audience');
            $table->boolean('requires_ack')->default(false)->after('is_pinned');
            $table->timestamp('archived_at')->nullable()->after('remove_on');
        });

        // Keep your existing notices: "important" ones become Important priority
        DB::table('bulletin_notices')->where('is_important', 1)->update(['priority' => 'important']);

        Schema::create('bulletin_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_notice_id')->constrained('bulletin_notices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            $table->unique(['bulletin_notice_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_reads');

        Schema::table('bulletin_notices', function (Blueprint $table) {
            $table->dropColumn(['priority', 'category', 'audience', 'is_pinned', 'requires_ack', 'archived_at']);
        });
    }
};