<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug', 64)->nullable()->unique();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->string('icon', 64)->default('layout-dashboard');
            $table->string('view', 16)->default('resumen');
            $table->string('group_by', 32)->nullable();
            $table->json('filters');
            $table->boolean('is_built_in')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->index(['owner_user_id', 'is_shared']);
            $table->index('is_built_in');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
