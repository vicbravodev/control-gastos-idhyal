<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_reports', function (Blueprint $table) {
            $table->string('document_type', 16)->default('factura')->after('reported_amount_cents');
        });
    }

    public function down(): void
    {
        Schema::table('expense_reports', function (Blueprint $table) {
            $table->dropColumn('document_type');
        });
    }
};
