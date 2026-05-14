<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_ledger_entries', function (Blueprint $table): void {
            $table->string('reason', 32)->nullable()->after('amount_cents');
            $table->index(['source_type', 'source_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::table('budget_ledger_entries', function (Blueprint $table): void {
            $table->dropIndex(['source_type', 'source_id', 'reason']);
            $table->dropColumn('reason');
        });
    }
};
