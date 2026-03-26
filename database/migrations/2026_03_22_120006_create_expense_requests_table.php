<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 64);
            $table->string('folio', 64)->nullable()->unique();
            $table->unsignedBigInteger('requested_amount_cents');
            $table->unsignedBigInteger('approved_amount_cents')->nullable();
            $table->text('concept');
            $table->string('delivery_method', 32);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_requests');
    }
};
