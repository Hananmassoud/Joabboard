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
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_logo')->nullable()->after('company_name');
            $table->date('company_established_date')->nullable()->after('company_logo');
            $table->string('company_website')->nullable()->after('company_established_date');
            $table->text('company_description')->nullable()->after('company_website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_logo',
                'company_established_date',
                'company_website',
                'company_description',
            ]);
        });
    }
};

