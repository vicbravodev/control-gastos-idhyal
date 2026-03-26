<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_policies', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 64);
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('requester_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['document_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_policies');
    }
};
