<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_events', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('event_type', 32);
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->text('note');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_events');
    }
};
