<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_events', function (Blueprint $table) {
            $table->foreignId('actor_user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('document_events', function (Blueprint $table) {
            $table->foreignId('actor_user_id')->nullable(false)->change();
        });
    }
};
