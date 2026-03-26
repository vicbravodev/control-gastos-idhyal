<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_request_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('payment_method', 32);
            $table->date('paid_on');
            $table->string('transfer_reference')->nullable();
            $table->timestamps();

            $table->index('expense_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
