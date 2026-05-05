<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacation_entitlement_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('calendar_year');
            $table->smallInteger('days');
            $table->string('reason', 255);
            $table->foreignId('granted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'calendar_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_entitlement_adjustments');
    }
};
