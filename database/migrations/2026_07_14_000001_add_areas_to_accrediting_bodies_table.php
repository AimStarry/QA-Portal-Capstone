<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accrediting_bodies', function (Blueprint $table) {
            $table->text('areas')->nullable();
        });

        // Seed default areas for baseline records
        $paascuAreas = [
            'Area 1: Leadership and Governance',
            'Area 2: Quality Assurance Systems',
            'Area 3: Resource Management',
            'Area 4: Teaching-Learning',
            'Area 5: Student Services',
            'Area 6: External Relations',
            'Area 7: Research',
            'Area 8: Educational Results',
        ];

        $pacucoaAreas = [
            'Area I: Philosophy and Objectives',
            'Area II: Faculty',
            'Area III: Instruction',
            'Area IV: Library',
            'Area V: Laboratories',
            'Area VI: Physical Plant and Facilities',
            'Area VII: Student Services',
            'Area VIII: Social Orientation and Community Involvement',
            'Area IX: Organization and Administration',
        ];

        $aunqaAreas = [
            'Criterion 1: Expected Learning Outcomes',
            'Criterion 2: Program Structure and Content',
            'Criterion 3: Teaching and Learning Strategy',
            'Criterion 4: Student Assessment',
            'Criterion 5: Academic Staff Quality',
            'Criterion 6: Support Staff Quality',
            'Criterion 7: Student Quality and Support',
            'Criterion 8: Infrastructure and Facilities',
            'Criterion 9: Quality Enhancement',
            'Criterion 10: Output',
        ];

        DB::table('accrediting_bodies')
            ->where('code', 'PAASCU')
            ->update(['areas' => json_encode($paascuAreas)]);

        DB::table('accrediting_bodies')
            ->where('code', 'PACUCOA')
            ->update(['areas' => json_encode($pacucoaAreas)]);

        DB::table('accrediting_bodies')
            ->where('code', 'AUN-QA')
            ->update(['areas' => json_encode($aunqaAreas)]);
    }

    public function down(): void
    {
        Schema::table('accrediting_bodies', function (Blueprint $table) {
            $table->dropColumn('areas');
        });
    }
};
