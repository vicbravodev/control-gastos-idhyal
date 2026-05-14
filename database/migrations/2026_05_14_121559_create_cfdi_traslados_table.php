<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_traslados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('expense_report_id')->constrained()->cascadeOnDelete();
            $table->string('impuesto', 3);
            $table->string('impuesto_label', 16);
            $table->string('tipo_factor', 16);
            $table->decimal('tasa_o_cuota', 12, 6)->nullable();
            $table->unsignedBigInteger('base_cents');
            $table->unsignedBigInteger('importe_cents');
            $table->string('nivel', 16);
            $table->unsignedSmallInteger('concepto_index')->nullable();
            $table->timestamps();

            $table->index(['expense_report_id', 'impuesto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_traslados');
    }
};
