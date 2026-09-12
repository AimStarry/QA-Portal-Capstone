<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_items', function (Blueprint $table) {
            if (!Schema::hasColumn('recommendation_items', 'status')) {
                $table->string('status')->default('pending')->after('text');
            }
            if (!Schema::hasColumn('recommendation_items', 'evidence_link')) {
                $table->text('evidence_link')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('recommendation_items', 'admin_remarks')) {
                $table->text('admin_remarks')->nullable()->after('evidence_link');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_items', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('recommendation_items', 'admin_remarks')) {
                $columns[] = 'admin_remarks';
            }
            if (Schema::hasColumn('recommendation_items', 'evidence_link')) {
                $columns[] = 'evidence_link';
            }
            if (Schema::hasColumn('recommendation_items', 'status')) {
                $columns[] = 'status';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
