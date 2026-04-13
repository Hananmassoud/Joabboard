<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('cv_file')->nullable()->after('cv_path'); // friendly/original name
            $table->string('ai_status', 20)->default('pending')->after('status'); // pending|processing|completed|failed
            $table->unsignedTinyInteger('skills_match')->nullable()->after('ai_status'); // 0..100
            $table->unsignedTinyInteger('experience_match')->nullable()->after('skills_match'); // 0..100
            $table->unsignedTinyInteger('education_match')->nullable()->after('experience_match'); // 0..100
            $table->unsignedTinyInteger('overall_match')->nullable()->after('education_match'); // 0..100
            $table->text('ai_summary')->nullable()->after('overall_match');
            $table->text('cv_text')->nullable()->after('ai_summary');
            $table->timestamp('ai_processed_at')->nullable()->after('cv_text');
            $table->text('ai_error')->nullable()->after('ai_processed_at');

            $table->index(['job_id', 'overall_match']);
            $table->index(['job_id', 'ai_status']);
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['job_id', 'overall_match']);
            $table->dropIndex(['job_id', 'ai_status']);
            $table->dropColumn([
                'cv_file',
                'ai_status',
                'skills_match',
                'experience_match',
                'education_match',
                'overall_match',
                'ai_summary',
                'cv_text',
                'ai_processed_at',
                'ai_error',
            ]);
        });
    }
};

