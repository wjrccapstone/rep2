<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // A full field-by-field snapshot (label/before/after) for the detail modal's
            // table — richer than the single before_value/after_value pair used for the
            // one-line summary in the list and CSV export.
            //
            // Named "field_changes", not "changes" — Eloquent's base Model class already
            // declares an internal $changes property for dirty-tracking (getChanges()),
            // and accessing $this->changes from inside a model method resolves to that
            // property directly rather than going through the attribute accessor.
            $table->json('field_changes')->nullable()->after('after_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('field_changes');
        });
    }
};
