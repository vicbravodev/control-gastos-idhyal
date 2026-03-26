<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->morphs('budgetable');
            $table->date('period_starts_on');
            $table->date('period_ends_on');
            $table->unsignedBigInteger('amount_limit_cents');
            $table->unsignedSmallInteger('priority')->nullable();
            $table->timestamps();

            $table->index(
                ['budgetable_type', 'budgetable_id', 'period_starts_on', 'period_ends_on'],
                'budgets_budgetable_period_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
