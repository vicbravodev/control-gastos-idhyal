<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_request_approvals', function (Blueprint $table): void {
            $table->string('reason', 32)->default('initial')->after('approver_id');
            $table->index(['expense_request_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::table('expense_request_approvals', function (Blueprint $table): void {
            $table->dropIndex(['expense_request_id', 'reason']);
            $table->dropColumn('reason');
        });
    }
};
