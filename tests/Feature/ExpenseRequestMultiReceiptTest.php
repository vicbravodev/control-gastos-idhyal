<?php

namespace Tests\Feature;

use App\Enums\ApprovalInstanceStatus;
use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\ApprovalStepMode;
use App\Enums\BudgetLedgerEntryType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestApprovalReason;
use App\Enums\ExpenseRequestStatus;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Budget;
use App\Models\BudgetLedgerEntry;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Settlement;
use App\Models\User;
use App\Notifications\ExpenseRequests\ExpenseRequestOverCapExtensionRequestedNotification;
use App\Services\Approvals\ExpenseRequestApprovalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CfdiTestFixtures;
use Tests\TestCase;

class ExpenseRequestMultiReceiptTest extends TestCase
{
    use CfdiTestFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Build a request that is past payment and ready to receive receipts.
     *
     * @return array{0: User, 1: User, 2: ExpenseRequest}
     */
    private function awaitingReportSetup(int $approvedCents = 30_000_000): array
    {
        $requester = User::factory()->create();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
            'requested_amount_cents' => $approvedCents,
            'approved_amount_cents' => $approvedCents,
            'folio' => 'MR-'.uniqid(),
        ]);
        Payment::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'recorded_by_user_id' => $accounting->id,
            'amount_cents' => $approvedCents,
        ]);

        return [$requester, $accounting, $expenseRequest];
    }

    private function submitReceipt(User $requester, ExpenseRequest $expenseRequest, int $cents, ?string $label = null): ExpenseReport
    {
        $pdf = UploadedFile::fake()->create('c.pdf', 100, 'application/pdf');
        $xml = UploadedFile::fake()->createWithContent('c.xml', $this->cfdiXmlForReportedCents($cents));

        $payload = [
            'reported_amount_cents' => $cents,
            'document_type' => 'factura',
            'pdf' => $pdf,
            'xml' => $xml,
        ];
        if ($label !== null) {
            $payload['label'] = $label;
        }

        $this->actingAs($requester)
            ->post(route('expense-requests.expense-reports.store', $expenseRequest), $payload)
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        return ExpenseReport::query()
            ->where('expense_request_id', $expenseRequest->id)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    public function test_can_attach_multiple_receipts_under_one_request(): void
    {
        Storage::fake('local');
        Notification::fake();
        [$requester, , $expenseRequest] = $this->awaitingReportSetup(30_000_000);

        $this->submitReceipt($requester, $expenseRequest, 10_000_000, 'Hotel');
        $this->submitReceipt($requester, $expenseRequest, 10_000_000, 'Vuelo');
        $this->submitReceipt($requester, $expenseRequest, 10_000_000, 'Viáticos');

        $expenseRequest->refresh();
        $this->assertSame(3, $expenseRequest->expenseReports()->count());
        $this->assertSame(30_000_000, (int) $expenseRequest->expenseReports()->sum('reported_amount_cents'));
        $this->assertSame(ExpenseRequestStatus::ExpenseReportInReview, $expenseRequest->status);
    }

    public function test_over_cap_triggers_extra_approval(): void
    {
        Storage::fake('local');
        Notification::fake();

        $this->createOneStepPolicy('contabilidad');

        $requester = User::factory()->forRole('asesor')->create();
        $accounting = User::factory()->forRole('contabilidad')->create();

        Budget::factory()->forBudgetable('user', $requester->id)->create([
            'period_starts_on' => now()->subYear()->toDateString(),
            'period_ends_on' => now()->addYear()->toDateString(),
            'amount_limit_cents' => 100_000_000,
            'priority' => 1,
        ]);

        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
            'requested_amount_cents' => 30_000_000,
            'approved_amount_cents' => 30_000_000,
            'folio' => 'MR-OC-'.uniqid(),
        ]);
        Payment::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'recorded_by_user_id' => $accounting->id,
            'amount_cents' => 30_000_000,
        ]);

        // Pre-existing initial commit ledger entry.
        $budget = Budget::query()->firstOrFail();
        BudgetLedgerEntry::query()->create([
            'budget_id' => $budget->id,
            'entry_type' => BudgetLedgerEntryType::Commit,
            'amount_cents' => 30_000_000,
            'reason' => ExpenseRequestApprovalReason::Initial->value,
            'source_type' => $expenseRequest->getMorphClass(),
            'source_id' => $expenseRequest->getKey(),
        ]);

        $this->submitReceipt($requester, $expenseRequest, 20_000_000, 'Hotel');
        $this->submitReceipt($requester, $expenseRequest, 15_000_000, 'Vuelo');

        $expenseRequest->refresh();
        $overCap = $expenseRequest->approvals()
            ->where('reason', ExpenseRequestApprovalReason::OverCapExtension)
            ->get();
        $this->assertCount(1, $overCap, 'Expected one over-cap approval row from the one-step policy.');
        $this->assertSame(ApprovalInstanceStatus::Pending, $overCap->first()->status);

        // Approve the over-cap chain
        app(ExpenseRequestApprovalService::class)
            ->approve($overCap->first(), $accounting);

        // A second ledger entry with reason=over_cap_extension and only the delta should appear.
        $deltaEntries = BudgetLedgerEntry::query()
            ->where('source_type', $expenseRequest->getMorphClass())
            ->where('source_id', $expenseRequest->getKey())
            ->where('entry_type', BudgetLedgerEntryType::Commit)
            ->where('reason', ExpenseRequestApprovalReason::OverCapExtension->value)
            ->get();
        $this->assertCount(1, $deltaEntries);
        $this->assertSame(5_000_000, (int) $deltaEntries->first()->amount_cents);
    }

    public function test_per_receipt_validation_independent(): void
    {
        Storage::fake('local');
        Notification::fake();
        [$requester, $accounting, $expenseRequest] = $this->awaitingReportSetup(30_000_000);

        $hotel = $this->submitReceipt($requester, $expenseRequest, 10_000_000, 'Hotel');
        $vuelo = $this->submitReceipt($requester, $expenseRequest, 10_000_000, 'Vuelo');

        // Approve hotel
        $this->actingAs($accounting)
            ->post(route('expense-requests.expense-reports.approve', [
                'expenseRequest' => $expenseRequest->id,
                'expenseReport' => $hotel->id,
            ]), ['note' => 'OK'])
            ->assertRedirect();

        // Reject vuelo
        $this->actingAs($accounting)
            ->post(route('expense-requests.expense-reports.reject', [
                'expenseRequest' => $expenseRequest->id,
                'expenseReport' => $vuelo->id,
            ]), ['note' => 'Factura ilegible'])
            ->assertRedirect();

        $hotel->refresh();
        $vuelo->refresh();
        $expenseRequest->refresh();

        $this->assertSame(ExpenseReportStatus::Approved, $hotel->status);
        $this->assertSame(ExpenseReportStatus::Rejected, $vuelo->status);
        // Not all rejected → request stays in review status.
        $this->assertSame(ExpenseRequestStatus::ExpenseReportInReview, $expenseRequest->status);
        // No settlement until all reports approved.
        $this->assertNull($expenseRequest->settlement);
    }

    public function test_settlement_created_only_when_all_validated_and_over_cap_cleared(): void
    {
        Storage::fake('local');
        Notification::fake();
        [$requester, $accounting, $expenseRequest] = $this->awaitingReportSetup(30_000_000);

        $hotel = $this->submitReceipt($requester, $expenseRequest, 10_000_000, 'Hotel');
        $vuelo = $this->submitReceipt($requester, $expenseRequest, 10_000_000, 'Vuelo');
        $viaticos = $this->submitReceipt($requester, $expenseRequest, 10_000_000, 'Viáticos');

        // Approve first two — settlement should not appear yet.
        $this->actingAs($accounting)
            ->post(route('expense-requests.expense-reports.approve', [
                'expenseRequest' => $expenseRequest->id,
                'expenseReport' => $hotel->id,
            ]))
            ->assertRedirect();
        $this->actingAs($accounting)
            ->post(route('expense-requests.expense-reports.approve', [
                'expenseRequest' => $expenseRequest->id,
                'expenseReport' => $vuelo->id,
            ]))
            ->assertRedirect();

        $this->assertNull(Settlement::query()->first());

        // Approve last — now settlement appears.
        $this->actingAs($accounting)
            ->post(route('expense-requests.expense-reports.approve', [
                'expenseRequest' => $expenseRequest->id,
                'expenseReport' => $viaticos->id,
            ]))
            ->assertRedirect();

        $expenseRequest->refresh();
        $settlement = Settlement::query()->firstOrFail();
        $this->assertSame($expenseRequest->id, $settlement->expense_request_id);
        $this->assertSame(0, $settlement->difference_cents);
        $this->assertSame(ExpenseRequestStatus::Closed, $expenseRequest->status);
    }

    public function test_duplicate_cfdi_uuid_across_receipts_rejected(): void
    {
        Storage::fake('local');
        Notification::fake();
        [$requester, , $expenseRequest] = $this->awaitingReportSetup(30_000_000);

        $uuid = '00000000-0000-4000-A000-000000000077';

        $pdf1 = UploadedFile::fake()->create('a.pdf', 100, 'application/pdf');
        $xml1 = UploadedFile::fake()->createWithContent('a.xml', $this->cfdiXmlForReportedCents(10_000_000, uuid: $uuid));
        $this->actingAs($requester)
            ->post(route('expense-requests.expense-reports.store', $expenseRequest), [
                'reported_amount_cents' => 10_000_000,
                'document_type' => 'factura',
                'pdf' => $pdf1,
                'xml' => $xml1,
                'label' => 'Hotel',
            ])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $pdf2 = UploadedFile::fake()->create('b.pdf', 100, 'application/pdf');
        $xml2 = UploadedFile::fake()->createWithContent('b.xml', $this->cfdiXmlForReportedCents(10_000_000, uuid: $uuid));
        $this->actingAs($requester)
            ->post(route('expense-requests.expense-reports.store', $expenseRequest), [
                'reported_amount_cents' => 10_000_000,
                'document_type' => 'factura',
                'pdf' => $pdf2,
                'xml' => $xml2,
                'label' => 'Vuelo',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('expense_report');

        $this->assertSame(1, $expenseRequest->expenseReports()->count());
    }

    public function test_over_cap_notifies_first_step_approvers_and_appears_in_pending_tray(): void
    {
        Storage::fake('local');
        Notification::fake();

        $this->createOneStepPolicy('contabilidad');

        $requester = User::factory()->forRole('asesor')->create();
        $accounting = User::factory()->forRole('contabilidad')->create();

        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
            'requested_amount_cents' => 20_000,
            'approved_amount_cents' => 20_000,
            'folio' => 'MR-OC-NOTIF-'.uniqid(),
        ]);
        Payment::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'recorded_by_user_id' => $accounting->id,
            'amount_cents' => 20_000,
        ]);

        $this->submitReceipt($requester, $expenseRequest, 32_000, 'Gas');

        $expenseRequest->refresh();
        $this->assertSame(ExpenseRequestStatus::ExpenseReportInReview, $expenseRequest->status);

        Notification::assertSentTo(
            $accounting,
            ExpenseRequestOverCapExtensionRequestedNotification::class,
            fn ($n) => $n->expenseRequest->id === $expenseRequest->id,
        );

        $this->actingAs($accounting)
            ->get(route('expense-requests.approvals.pending'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('expense-requests/approvals/pending')
                ->has('items', 1)
                ->where('items.0.expense_request_id', $expenseRequest->id));
    }

    private function createOneStepPolicy(string $approverRoleSlug): void
    {
        $role = Role::query()->where('slug', $approverRoleSlug)->firstOrFail();

        $policy = ApprovalPolicy::factory()->create([
            'document_type' => ApprovalPolicyDocumentType::ExpenseRequest,
        ]);
        ApprovalPolicyStep::factory()->create([
            'approval_policy_id' => $policy->id,
            'step_order' => 1,
            'approver_type' => 'role',
            'approver_id' => $role->id,
            'step_mode' => ApprovalStepMode::Sequential,
        ]);
    }
}
