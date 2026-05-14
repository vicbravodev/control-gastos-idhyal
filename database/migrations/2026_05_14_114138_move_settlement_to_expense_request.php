<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settlements', function (Blueprint $table): void {
            $table->dropUnique(['expense_report_id']);
        });
        Schema::table('settlements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_report_id');
        });
        Schema::table('settlements', function (Blueprint $table): void {
            $table->foreignId('expense_request_id')
                ->after('id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table): void {
            $table->dropUnique(['expense_request_id']);
        });
        Schema::table('settlements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_request_id');
        });
        Schema::table('settlements', function (Blueprint $table): void {
            $table->foreignId('expense_report_id')
                ->after('id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
        });
    }
};
