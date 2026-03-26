<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacation_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('calendar_year');
            $table->unsignedSmallInteger('days_allocated');
            $table->unsignedSmallInteger('days_used')->default(0);
            $table->foreignId('vacation_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'calendar_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_entitlements');
    }
};
