<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename email column to username
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('email', 'username');
        });

        // Drop the old email unique index
        $indexExists = DB::select("SELECT indexname FROM pg_indexes WHERE indexname = 'unique_email_not_deleted'");
        if (!empty($indexExists)) {
            DB::statement('DROP INDEX IF EXISTS unique_email_not_deleted');
        }

        // Create new partial unique index for username (unique only when deleted_at IS NULL)
        $partialIndexExists = DB::select("SELECT indexname FROM pg_indexes WHERE indexname = 'unique_username_not_deleted'");
        if (empty($partialIndexExists)) {
            DB::statement('CREATE UNIQUE INDEX unique_username_not_deleted ON users(username) WHERE deleted_at IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop username unique index
        DB::statement('DROP INDEX IF EXISTS unique_username_not_deleted');

        // Rename username back to email
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('username', 'email');
        });

        // Recreate email unique index
        $partialIndexExists = DB::select("SELECT indexname FROM pg_indexes WHERE indexname = 'unique_email_not_deleted'");
        if (empty($partialIndexExists)) {
            DB::statement('CREATE UNIQUE INDEX unique_email_not_deleted ON users(email) WHERE deleted_at IS NULL');
        }
    }
};
