<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_report_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 64);
            $table->unsignedBigInteger('basis_amount_cents');
            $table->unsignedBigInteger('reported_amount_cents');
            $table->bigInteger('difference_cents');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
