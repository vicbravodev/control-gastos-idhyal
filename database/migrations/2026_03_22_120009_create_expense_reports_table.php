<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 64);
            $table->unsignedBigInteger('reported_amount_cents');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_reports');
    }
};
