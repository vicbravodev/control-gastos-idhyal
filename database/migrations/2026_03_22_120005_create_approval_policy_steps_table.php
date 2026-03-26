<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_policy_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_policy_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->string('combine_with_next', 8)->default('and');
            $table->timestamps();

            $table->unique(['approval_policy_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_policy_steps');
    }
};
