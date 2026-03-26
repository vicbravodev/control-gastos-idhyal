<?php

namespace Tests\Feature;

use App\Enums\DocumentEventType;
use App\Enums\ExpenseReportStatus;
use App\Enums\ExpenseRequestStatus;
use App\Enums\SettlementStatus;
use App\Models\DocumentEvent;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\Settlement;
use App\Models\User;
use App\Notifications\ExpenseRequests\SettlementClosedNotification;
use App\Notifications\ExpenseRequests\SettlementLiquidatedNotification;
use App\Notifications\ExpenseRequests\SettlementPendingReminderNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettlementLiquidationHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: User, 1: ExpenseRequest, 2: User}
     */
    private function settlementPendingSetup(int $reportedCents = 80_000): array
    {
        $requester = User::factory()->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::SettlementPending,
            'requested_amount_cents' => 100_000,
            'approved_amount_cents' => 100_000,
            'folio' => 'EXP-SET-'.uniqid(),
            'concept_description' => 'Liquidación test',
        ]);

        $report = ExpenseReport::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'status' => ExpenseReportStatus::Approved,
            'reported_amount_cents' => $reportedCents,
            'submitted_at' => now(),
        ]);

        Settlement::query()->create([
            'expense_report_id' => $report->id,
            'status' => SettlementStatus::PendingUserReturn,
            'basis_amount_cents' => 100_000,
            'reported_amount_cents' => $reportedCents,
            'difference_cents' => 100_000 - $reportedCents,
        ]);

        $accounting = User::factory()->forRole('contabilidad')->create();

        return [$requester, $expenseRequest, $accounting];
    }

    public function test_accounting_can_record_liquidation_and_close_settlement(): void
    {
        $this->seedRoles();
        Notification::fake();
        Storage::fake('local');

        [$requester, $expenseRequest, $accounting] = $this->settlementPendingSetup();

        $evidence = UploadedFile::fake()->create('liq.pdf', 100, 'application/pdf');

        $this->actingAs($accounting)
            ->post(route('expense-requests.settlement.liquidation.store', $expenseRequest), [
                'evidence' => $evidence,
            ])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $settlement = Settlement::query()->firstOrFail();
        $this->assertSame(SettlementStatus::Settled, $settlement->status);
        $this->assertTrue($settlement->attachments()->exists());

        $this->assertTrue(DocumentEvent::query()
            ->where('subject_id', $expenseRequest->id)
            ->where('event_type', DocumentEventType::SettlementLiquidationRecorded)
            ->exists());

        Notification::assertSentTo($requester, SettlementLiquidatedNotification::class);

        $this->actingAs($accounting)
            ->post(route('expense-requests.settlement.close', $expenseRequest), [
                'note' => 'Caso cerrado',
            ])
            ->assertRedirect(route('expense-requests.show', $expenseRequest));

        $expenseRequest->refresh();
        $settlement->refresh();
        $this->assertSame(ExpenseRequestStatus::Closed, $expenseRequest->status);
        $this->assertSame(SettlementStatus::Closed, $settlement->status);

        $this->assertTrue(DocumentEvent::query()
            ->where('subject_id', $expenseRequest->id)
            ->where('event_type', DocumentEventType::SettlementClosed)
            ->exists());

        Notification::assertSentTo($requester, SettlementClosedNotification::class);
    }

    public function test_requester_cannot_record_liquidation(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        [$requester, $expenseRequest] = $this->settlementPendingSetup();

        $evidence = UploadedFile::fake()->create('liq.pdf', 100, 'application/pdf');

        $this->actingAs($requester)
            ->post(route('expense-requests.settlement.liquidation.store', $expenseRequest), [
                'evidence' => $evidence,
            ])
            ->assertForbidden();
    }

    public function test_accounting_cannot_liquidate_when_settlement_already_settled(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        [, $expenseRequest, $accounting] = $this->settlementPendingSetup();
        $settlement = Settlement::query()->firstOrFail();
        $settlement->update(['status' => SettlementStatus::Settled]);

        $evidence = UploadedFile::fake()->create('liq.pdf', 100, 'application/pdf');

        $this->actingAs($accounting)
            ->post(route('expense-requests.settlement.liquidation.store', $expenseRequest), [
                'evidence' => $evidence,
            ])
            ->assertForbidden();
    }

    public function test_requester_and_accounting_can_download_settlement_liquidation_pdf_and_evidence(): void
    {
        $this->seedRoles();
        Notification::fake();
        Storage::fake('local');

        [$requester, $expenseRequest, $accounting] = $this->settlementPendingSetup();
        $evidence = UploadedFile::fake()->create('liq.pdf', 100, 'application/pdf');

        $this->actingAs($accounting)
            ->post(route('expense-requests.settlement.liquidation.store', $expenseRequest), [
                'evidence' => $evidence,
            ])
            ->assertSessionHasNoErrors();

        $expenseRequest->refresh();
        $expenseRequest->load('expenseReport.settlement.attachments');
        $attachment = $expenseRequest->expenseReport->settlement->attachments->firstOrFail();

        $this->actingAs($requester)
            ->get(route('expense-requests.receipts.settlement-liquidation', $expenseRequest))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($accounting)
            ->get(route('expense-requests.receipts.settlement-liquidation', $expenseRequest))
            ->assertOk();

        $this->actingAs($requester)
            ->get(route('expense-requests.settlements.liquidation-evidence', [
                'expense_request' => $expenseRequest,
                'attachment' => $attachment,
            ]))
            ->assertOk();
    }

    public function test_cannot_download_settlement_liquidation_receipt_without_liquidation(): void
    {
        $this->seedRoles();
        [$requester, $expenseRequest] = $this->settlementPendingSetup();

        $this->actingAs($requester)
            ->get(route('expense-requests.receipts.settlement-liquidation', $expenseRequest))
            ->assertForbidden();
    }

    public function test_cannot_download_foreign_settlement_liquidation_evidence(): void
    {
        $this->seedRoles();
        Notification::fake();
        Storage::fake('local');

        [$requesterA, $expenseRequestA, $accounting] = $this->settlementPendingSetup();
        [$requesterB, $expenseRequestB] = $this->settlementPendingSetup(70_000);

        $evidence = UploadedFile::fake()->create('liq.pdf', 100, 'application/pdf');
        $this->actingAs($accounting)
            ->post(route('expense-requests.settlement.liquidation.store', $expenseRequestA), [
                'evidence' => $evidence,
            ])
            ->assertSessionHasNoErrors();

        $expenseRequestA->refresh();
        $expenseRequestA->load('expenseReport.settlement.attachments');
        $attachmentA = $expenseRequestA->expenseReport->settlement->attachments->firstOrFail();

        $this->actingAs($requesterA)
            ->get(route('expense-requests.settlements.liquidation-evidence', [
                'expense_request' => $expenseRequestB,
                'attachment' => $attachmentA,
            ]))
            ->assertForbidden();

        $this->actingAs($requesterB)
            ->get(route('expense-requests.settlements.liquidation-evidence', [
                'expense_request' => $expenseRequestB,
                'attachment' => $attachmentA,
            ]))
            ->assertForbidden();
    }

    public function test_reminder_command_notifies_after_one_day_pending(): void
    {
        $this->seedRoles();
        Notification::fake();

        [$requester, $expenseRequest, $accounting] = $this->settlementPendingSetup();

        $settlement = Settlement::query()->firstOrFail();
        $settlement->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->save();

        $this->artisan('settlements:send-pending-reminders')->assertSuccessful();

        Notification::assertSentTo($requester, SettlementPendingReminderNotification::class);
        Notification::assertSentTo($accounting, SettlementPendingReminderNotification::class);
    }
}
