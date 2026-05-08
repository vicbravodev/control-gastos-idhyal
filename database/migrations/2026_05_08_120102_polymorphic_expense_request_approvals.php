<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_request_approvals', function (Blueprint $table) {
            $table->string('approver_type', 32)->nullable()->after('step_order');
            $table->unsignedBigInteger('approver_id')->nullable()->after('approver_type');
        });

        DB::table('expense_request_approvals')->orderBy('id')->each(function ($row): void {
            DB::table('expense_request_approvals')->where('id', $row->id)->update([
                'approver_type' => 'role',
                'approver_id' => $row->role_id,
            ]);
        });

        Schema::table('expense_request_approvals', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::table('expense_request_approvals', function (Blueprint $table) {
            $table->string('approver_type', 32)->nullable(false)->change();
            $table->unsignedBigInteger('approver_id')->nullable(false)->change();

            $table->index(['approver_type', 'approver_id']);
        });
    }

    public function down(): void
    {
        Schema::table('expense_request_approvals', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('step_order')->constrained()->restrictOnDelete();
        });

        DB::table('expense_request_approvals')->orderBy('id')->each(function ($row): void {
            DB::table('expense_request_approvals')->where('id', $row->id)->update([
                'role_id' => $row->approver_type === 'role' ? $row->approver_id : null,
            ]);
        });

        Schema::table('expense_request_approvals', function (Blueprint $table) {
            $table->dropIndex(['approver_type', 'approver_id']);
            $table->dropColumn(['approver_type', 'approver_id']);
        });
    }
};
