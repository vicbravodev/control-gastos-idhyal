<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_concepts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
        });

        $defaultConceptId = (int) DB::table('expense_concepts')->insertGetId([
            'name' => 'General',
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('expense_requests', function (Blueprint $table) use ($defaultConceptId): void {
            $table->foreignId('expense_concept_id')
                ->after('approved_amount_cents')
                ->default($defaultConceptId)
                ->constrained('expense_concepts')
                ->restrictOnDelete();
            $table->text('concept_description')->nullable()->after('expense_concept_id');
        });

        Schema::table('expense_requests', function (Blueprint $table): void {
            $table->dropColumn('concept');
        });
    }

    public function down(): void
    {
        Schema::table('expense_requests', function (Blueprint $table): void {
            $table->text('concept')->nullable()->after('approved_amount_cents');
        });

        $rows = DB::table('expense_requests')
            ->join('expense_concepts', 'expense_requests.expense_concept_id', '=', 'expense_concepts.id')
            ->select('expense_requests.id', 'expense_concepts.name')
            ->get();

        foreach ($rows as $row) {
            DB::table('expense_requests')
                ->where('id', $row->id)
                ->update(['concept' => $row->name]);
        }

        Schema::table('expense_requests', function (Blueprint $table): void {
            $table->dropForeign(['expense_concept_id']);
            $table->dropColumn(['expense_concept_id', 'concept_description']);
        });

        Schema::dropIfExists('expense_concepts');
    }
};
