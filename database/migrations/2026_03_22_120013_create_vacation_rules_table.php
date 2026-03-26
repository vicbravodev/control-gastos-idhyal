<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->decimal('min_years_service', 4, 1)->default(0);
            $table->decimal('max_years_service', 4, 1)->nullable();
            $table->unsignedSmallInteger('days_granted_per_year');
            $table->unsignedSmallInteger('max_days_per_request')->nullable();
            $table->unsignedSmallInteger('max_days_per_month')->nullable();
            $table->unsignedSmallInteger('max_days_per_quarter')->nullable();
            $table->unsignedSmallInteger('max_days_per_year')->nullable();
            $table->json('blackout_dates')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_rules');
    }
};
