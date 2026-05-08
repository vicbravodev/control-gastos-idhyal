<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_policy_steps', function (Blueprint $table) {
            $table->string('approver_type', 32)->nullable()->after('step_order');
            $table->unsignedBigInteger('approver_id')->nullable()->after('approver_type');
            $table->string('step_mode', 32)->nullable()->after('approver_id');
        });

        DB::table('approval_policy_steps')->orderBy('id')->each(function ($row): void {
            DB::table('approval_policy_steps')->where('id', $row->id)->update([
                'approver_type' => 'role',
                'approver_id' => $row->role_id,
                'step_mode' => $row->combine_with_next === 'or' ? 'any_of' : 'sequential',
            ]);
        });

        Schema::table('approval_policy_steps', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'combine_with_next']);
        });

        Schema::table('approval_policy_steps', function (Blueprint $table) {
            $table->string('approver_type', 32)->nullable(false)->change();
            $table->unsignedBigInteger('approver_id')->nullable(false)->change();
            $table->string('step_mode', 32)->nullable(false)->change();

            $table->index(['approver_type', 'approver_id']);
        });
    }

    public function down(): void
    {
        Schema::table('approval_policy_steps', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('step_order')->constrained()->restrictOnDelete();
            $table->string('combine_with_next', 8)->default('and')->after('role_id');
        });

        DB::table('approval_policy_steps')->orderBy('id')->each(function ($row): void {
            DB::table('approval_policy_steps')->where('id', $row->id)->update([
                'role_id' => $row->approver_type === 'role' ? $row->approver_id : null,
                'combine_with_next' => $row->step_mode === 'any_of' ? 'or' : 'and',
            ]);
        });

        Schema::table('approval_policy_steps', function (Blueprint $table) {
            $table->dropIndex(['approver_type', 'approver_id']);
            $table->dropColumn(['approver_type', 'approver_id', 'step_mode']);
        });
    }
};
