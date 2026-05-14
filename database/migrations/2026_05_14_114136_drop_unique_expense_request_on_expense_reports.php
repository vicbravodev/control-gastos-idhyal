<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_reports', function (Blueprint $table): void {
            $table->dropUnique(['expense_request_id']);
            $table->index('expense_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('expense_reports', function (Blueprint $table): void {
            $table->dropIndex(['expense_request_id']);
            $table->unique('expense_request_id');
        });
    }
};
