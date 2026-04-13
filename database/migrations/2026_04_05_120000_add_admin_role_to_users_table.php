<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Admin access is handled via `is_admin` on users (see add_is_admin migration).
 * This file remains so existing migration history stays valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
