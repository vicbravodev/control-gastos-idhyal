<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_impuestos_locales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('expense_report_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 16);
            $table->string('tipo', 16);
            $table->decimal('tasa', 6, 4)->nullable();
            $table->unsignedBigInteger('importe_cents');
            $table->timestamps();

            $table->index(['expense_report_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_impuestos_locales');
    }
};
