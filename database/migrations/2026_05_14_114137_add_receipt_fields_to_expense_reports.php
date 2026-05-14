<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_reports', function (Blueprint $table): void {
            $table->string('label', 64)->nullable()->after('document_type');
            $table->foreignId('reviewer_user_id')->nullable()->after('label')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewer_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('expense_reports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewer_user_id');
            $table->dropColumn(['label', 'reviewed_at']);
        });
    }
};
