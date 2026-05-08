<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_policies', function (Blueprint $table) {
            $table->string('applies_to_type', 32)->nullable()->after('version');
            $table->unsignedBigInteger('applies_to_id')->nullable()->after('applies_to_type');
            $table->index(['applies_to_type', 'applies_to_id']);
        });

        DB::table('approval_policies')
            ->whereNotNull('requester_role_id')
            ->update([
                'applies_to_type' => 'role',
                'applies_to_id' => DB::raw('requester_role_id'),
            ]);

        Schema::table('approval_policies', function (Blueprint $table) {
            $table->dropForeign(['requester_role_id']);
            $table->dropColumn('requester_role_id');
        });
    }

    public function down(): void
    {
        Schema::table('approval_policies', function (Blueprint $table) {
            $table->foreignId('requester_role_id')->nullable()->after('version')->constrained('roles')->nullOnDelete();
        });

        DB::table('approval_policies')
            ->where('applies_to_type', 'role')
            ->whereNotNull('applies_to_id')
            ->update(['requester_role_id' => DB::raw('applies_to_id')]);

        Schema::table('approval_policies', function (Blueprint $table) {
            $table->dropIndex(['applies_to_type', 'applies_to_id']);
            $table->dropColumn(['applies_to_type', 'applies_to_id']);
        });
    }
};
