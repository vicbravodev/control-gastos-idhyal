<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_requests', function (Blueprint $table) {
            $table->boolean('is_reimbursement')->default(false)->after('delivery_method');
            $table->index('is_reimbursement');
        });
    }

    public function down(): void
    {
        Schema::table('expense_requests', function (Blueprint $table) {
            $table->dropIndex(['is_reimbursement']);
            $table->dropColumn('is_reimbursement');
        });
    }
};
