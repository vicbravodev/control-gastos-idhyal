<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacation_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacation_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->string('status', 32);
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['vacation_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_request_approvals');
    }
};
