<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('ai_job_field')->nullable()->after('ai_status');
            $table->decimal('ai_relevant_experience_years', 5, 1)->nullable()->after('experience_match');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['ai_job_field', 'ai_relevant_experience_years']);
        });
    }
};

