<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_reports', function (Blueprint $table) {
            $table->string('cfdi_uuid', 64)->nullable()->after('document_type');
            $table->string('cfdi_emisor_rfc', 20)->nullable()->after('cfdi_uuid');
            $table->string('cfdi_emisor_nombre', 255)->nullable()->after('cfdi_emisor_rfc');
            $table->string('cfdi_receptor_rfc', 20)->nullable()->after('cfdi_emisor_nombre');
            $table->string('cfdi_receptor_nombre', 255)->nullable()->after('cfdi_receptor_rfc');
            $table->dateTime('cfdi_fecha')->nullable()->after('cfdi_receptor_nombre');
            $table->string('cfdi_serie', 32)->nullable()->after('cfdi_fecha');
            $table->string('cfdi_folio', 64)->nullable()->after('cfdi_serie');
            $table->string('cfdi_forma_pago', 8)->nullable()->after('cfdi_folio');
            $table->string('cfdi_metodo_pago', 8)->nullable()->after('cfdi_forma_pago');
            $table->string('cfdi_uso_cfdi', 8)->nullable()->after('cfdi_metodo_pago');
            $table->json('cfdi_conceptos')->nullable()->after('cfdi_uso_cfdi');

            $table->unique('cfdi_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('expense_reports', function (Blueprint $table) {
            $table->dropUnique(['cfdi_uuid']);
            $table->dropColumn([
                'cfdi_uuid',
                'cfdi_emisor_rfc',
                'cfdi_emisor_nombre',
                'cfdi_receptor_rfc',
                'cfdi_receptor_nombre',
                'cfdi_fecha',
                'cfdi_serie',
                'cfdi_folio',
                'cfdi_forma_pago',
                'cfdi_metodo_pago',
                'cfdi_uso_cfdi',
                'cfdi_conceptos',
            ]);
        });
    }
};
