<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 150);
            $table->string('module', 50);
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['module', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
