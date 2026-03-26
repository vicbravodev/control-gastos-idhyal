<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->restrictOnDelete();
            $table->string('entry_type', 32);
            $table->unsignedBigInteger('amount_cents');
            $table->morphs('source');
            $table->foreignId('reverses_ledger_entry_id')
                ->nullable()
                ->constrained('budget_ledger_entries')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['budget_id', 'entry_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_ledger_entries');
    }
};
