<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->json('required_skills')->nullable()->after('description');
            $table->unsignedSmallInteger('min_experience_years')->nullable()->after('required_skills');
            $table->string('education_level', 80)->nullable()->after('min_experience_years');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['required_skills', 'min_experience_years', 'education_level']);
        });
    }
};

