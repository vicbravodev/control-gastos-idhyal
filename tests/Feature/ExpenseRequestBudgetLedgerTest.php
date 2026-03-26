<?php

namespace Tests\Feature;

use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\BudgetLedgerEntryType;
use App\Enums\CombineWithNext;
use App\Enums\ExpenseRequestStatus;
use App\Enums\PaymentMethod;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Budget;
use App\Models\BudgetLedgerEntry;
use App\Models\ExpenseRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Approvals\ExpenseRequestApprovalService;
use App\Services\Payments\RecordExpenseRequestPayment;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseRequestBudgetLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    private function createTwoStepAndApprovalPolicy(): void
    {
        $coord = Role::query()->where('slug', 'coord_regional')->firstOrFail();
        $conta = Role::query()->where('slug', 'contabilidad')->firstOrFail();

        $policy = ApprovalPolicy::factory()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest,
        ]);

        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 1,
            'role_id' => $coord->id,
            'combine_with_next' => CombineWithNext::And,
        ]);
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 2,
            'role_id' => $conta->id,
            'combine_with_next' => CombineWithNext::And,
        ]);
    }

    public function test_final_approval_writes_commit_ledger_entry(): void
    {
        $this->seedRoles();
        $this->createTwoStepAndApprovalPolicy();

        $requester = User::factory()->forRole('asesor')->create();

        Budget::factory()->forBudgetable('user', $requester->id)->create([
            'period_starts_on' => now()->subYear()->toDateString(),
            'period_ends_on' => now()->addYear()->toDateString(),
            'amount_limit_cents' => 100_000_000,
            'priority' => 1,
        ]);

        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
            'requested_amount_cents' => 50_000,
        ]);

        $service = app(ExpenseRequestApprovalService::class);
        $service->startWorkflow($expense);

        $coordUser = User::factory()->forRole('coord_regional')->create();
        $contaUser = User::factory()->forRole('contabilidad')->create();

        $step1 = $expense->approvals()->where('step_order', 1)->firstOrFail();
        $step2 = $expense->approvals()->where('step_order', 2)->firstOrFail();

        $service->approve($step1, $coordUser);
        $service->approve($step2, $contaUser);

        $expense->refresh();
        $this->assertSame(ExpenseRequestStatus::PendingPayment, $expense->status);

        $this->assertSame(
            1,
            BudgetLedgerEntry::query()->where('entry_type', BudgetLedgerEntryType::Commit)->count(),
        );

        $entry = BudgetLedgerEntry::query()
            ->where('entry_type', BudgetLedgerEntryType::Commit)
            ->firstOrFail();

        $this->assertSame($expense->id, $entry->source_id);
        $this->assertSame('expense_request', $entry->source_type);
        $this->assertSame(50_000, $entry->amount_cents);
    }

    public function test_without_budget_no_commit_row(): void
    {
        $this->seedRoles();
        $this->createTwoStepAndApprovalPolicy();

        $requester = User::factory()->forRole('asesor')->create();

        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
            'requested_amount_cents' => 50_000,
        ]);

        $service = app(ExpenseRequestApprovalService::class);
        $service->startWorkflow($expense);

        $coordUser = User::factory()->forRole('coord_regional')->create();
        $contaUser = User::factory()->forRole('contabilidad')->create();

        $service->approve($expense->approvals()->where('step_order', 1)->firstOrFail(), $coordUser);
        $service->approve($expense->approvals()->where('step_order', 2)->firstOrFail(), $contaUser);

        $this->assertSame(
            0,
            BudgetLedgerEntry::query()->where('entry_type', BudgetLedgerEntryType::Commit)->count(),
        );
    }

    public function test_recording_payment_writes_spend_on_same_budget(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $this->createTwoStepAndApprovalPolicy();

        $requester = User::factory()->forRole('asesor')->create();

        $budget = Budget::factory()->forBudgetable('user', $requester->id)->create([
            'period_starts_on' => now()->subYear()->toDateString(),
            'period_ends_on' => now()->addYear()->toDateString(),
            'amount_limit_cents' => 100_000_000,
            'priority' => 1,
        ]);

        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::Submitted,
            'requested_amount_cents' => 40_000,
        ]);

        $service = app(ExpenseRequestApprovalService::class);
        $service->startWorkflow($expense);

        $coordUser = User::factory()->forRole('coord_regional')->create();
        $contaUser = User::factory()->forRole('contabilidad')->create();

        $service->approve($expense->approvals()->where('step_order', 1)->firstOrFail(), $coordUser);
        $service->approve($expense->approvals()->where('step_order', 2)->firstOrFail(), $contaUser);

        $expense->refresh();

        $accounting = User::factory()->forRole('contabilidad')->create();
        $record = app(RecordExpenseRequestPayment::class);
        $file = UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf');

        $payment = $record->record(
            $expense,
            $accounting,
            40_000,
            PaymentMethod::Transfer,
            new \DateTimeImmutable('2026-03-20'),
            'TRF-99',
            $file,
        );

        $this->assertSame(
            1,
            BudgetLedgerEntry::query()->where('entry_type', BudgetLedgerEntryType::Spend)->count(),
        );

        $spend = BudgetLedgerEntry::query()
            ->where('entry_type', BudgetLedgerEntryType::Spend)
            ->firstOrFail();

        $this->assertSame($budget->id, $spend->budget_id);
        $this->assertSame($payment->id, $spend->source_id);
        $this->assertSame('payment', $spend->source_type);
        $this->assertSame(40_000, $spend->amount_cents);
    }

    public function test_payment_without_commit_does_not_write_spend(): void
    {
        Storage::fake('local');
        $this->seedRoles();

        $requester = User::factory()->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::PendingPayment,
            'requested_amount_cents' => 100_000,
            'approved_amount_cents' => 100_000,
        ]);

        $accounting = User::factory()->forRole('contabilidad')->create();
        $record = app(RecordExpenseRequestPayment::class);
        $file = UploadedFile::fake()->create('comprobante.pdf', 120, 'application/pdf');

        $record->record(
            $expense,
            $accounting,
            100_000,
            PaymentMethod::Transfer,
            new \DateTimeImmutable('2026-03-20'),
            'TRF-001',
            $file,
        );

        $this->assertSame(
            0,
            BudgetLedgerEntry::query()->where('entry_type', BudgetLedgerEntryType::Spend)->count(),
        );
    }
}
