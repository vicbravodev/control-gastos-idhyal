<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DomainSchemaMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_tables_exist_after_migrations(): void
    {
        $tables = [
            'regions',
            'states',
            'roles',
            'approval_policies',
            'approval_policy_steps',
            'expense_requests',
            'expense_request_approvals',
            'payments',
            'expense_reports',
            'settlements',
            'budgets',
            'expense_concepts',
            'budget_ledger_entries',
            'vacation_rules',
            'vacation_entitlements',
            'vacation_requests',
            'vacation_request_approvals',
            'attachments',
            'document_events',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Expected table [{$table}] to exist."
            );
        }
    }

    public function test_users_table_has_profile_and_territory_columns(): void
    {
        foreach (['username', 'phone', 'region_id', 'state_id', 'role_id'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "Expected users.{$column} column to exist."
            );
        }
    }

    public function test_expense_requests_has_core_columns(): void
    {
        foreach (
            [
                'user_id',
                'status',
                'folio',
                'requested_amount_cents',
                'approved_amount_cents',
                'expense_concept_id',
                'concept_description',
                'delivery_method',
            ] as $column
        ) {
            $this->assertTrue(
                Schema::hasColumn('expense_requests', $column),
                "Expected expense_requests.{$column} column to exist."
            );
        }
    }

    public function test_budget_ledger_entries_has_morph_and_self_reference(): void
    {
        foreach (['budget_id', 'entry_type', 'amount_cents', 'source_type', 'source_id', 'reverses_ledger_entry_id'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('budget_ledger_entries', $column),
                "Expected budget_ledger_entries.{$column} column to exist."
            );
        }
    }

    public function test_document_events_has_audit_columns(): void
    {
        foreach (['subject_type', 'subject_id', 'event_type', 'actor_user_id', 'note'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('document_events', $column),
                "Expected document_events.{$column} column to exist."
            );
        }
    }
}
