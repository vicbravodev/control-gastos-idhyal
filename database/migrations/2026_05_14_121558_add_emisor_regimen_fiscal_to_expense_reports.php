<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_reports', function (Blueprint $table): void {
            $table->string('cfdi_emisor_regimen_fiscal', 8)->nullable()->after('cfdi_emisor_nombre');
            $table->boolean('cfdi_has_hidrocarburos_complement')->default(false)->after('cfdi_conceptos');
        });
    }

    public function down(): void
    {
        Schema::table('expense_reports', function (Blueprint $table): void {
            $table->dropColumn(['cfdi_emisor_regimen_fiscal', 'cfdi_has_hidrocarburos_complement']);
        });
    }
};
