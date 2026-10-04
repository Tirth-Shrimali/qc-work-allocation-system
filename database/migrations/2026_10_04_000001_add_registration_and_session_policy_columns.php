<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Self-registered accounts can sit in a "pending" state until approved.
        if (! Schema::hasColumn('users', 'approved_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('status');
            });
        }

        // Widen the status enum on MySQL/MariaDB only (additive — existing rows untouched).
        // SQLite (test environment) stores enum as a loose text column, no change needed.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active','inactive','pending') NOT NULL DEFAULT 'active'");
        }

        // The inactivity timeout each user chose at login, stored with their session record.
        if (! Schema::hasColumn('user_sessions', 'timeout_minutes')) {
            Schema::table('user_sessions', function (Blueprint $table) {
                $table->unsignedInteger('timeout_minutes')->nullable()->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'approved_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }

        if (Schema::hasColumn('user_sessions', 'timeout_minutes')) {
            Schema::table('user_sessions', function (Blueprint $table) {
                $table->dropColumn('timeout_minutes');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
        }
    }
};
