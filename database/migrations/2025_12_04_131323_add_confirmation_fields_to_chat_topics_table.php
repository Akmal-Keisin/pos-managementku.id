<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_topics', function (Blueprint $table) {
            $table->string('confirmation_action')->nullable()->after('last_message_at');
            $table->json('confirmation_payload')->nullable()->after('confirmation_action');
        });
    }

    public function down(): void
    {
        Schema::table('chat_topics', function (Blueprint $table) {
            $table->dropColumn(['confirmation_action', 'confirmation_payload']);
        });
    }
};
