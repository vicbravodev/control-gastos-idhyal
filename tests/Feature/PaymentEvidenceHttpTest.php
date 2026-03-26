<?php

namespace Tests\Feature;

use App\Enums\ExpenseRequestStatus;
use App\Models\Attachment;
use App\Models\ExpenseRequest;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentEvidenceHttpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: ExpenseRequest, 2: User, 3: Payment, 4: Attachment}
     */
    private function expenseRequestWithPaymentEvidence(): array
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');

        $owner = User::factory()->create();
        $accounting = User::factory()->forRole('contabilidad')->create();
        $expenseRequest = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
            'approved_amount_cents' => 50_000,
        ]);

        $payment = Payment::factory()->create([
            'expense_request_id' => $expenseRequest->id,
            'recorded_by_user_id' => $accounting->id,
            'amount_cents' => 50_000,
        ]);

        Storage::disk('local')->put('payments/'.$payment->id.'/evidence.pdf', 'pdf-bytes');

        $attachment = Attachment::query()->create([
            'attachable_type' => $payment->getMorphClass(),
            'attachable_id' => $payment->id,
            'uploaded_by_user_id' => $accounting->id,
            'disk' => 'local',
            'path' => 'payments/'.$payment->id.'/evidence.pdf',
            'original_filename' => 'comprobante.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
        ]);

        return [$owner, $expenseRequest, $accounting, $payment, $attachment];
    }

    public function test_requester_and_accounting_can_download_payment_evidence(): void
    {
        [$owner, $expenseRequest, $accounting, , $attachment] = $this->expenseRequestWithPaymentEvidence();

        $this->actingAs($owner)
            ->get(route('expense-requests.payments.payment-evidence', [
                'expense_request' => $expenseRequest,
                'attachment' => $attachment,
            ]))
            ->assertOk()
            ->assertDownload('comprobante.pdf');

        $this->actingAs($accounting)
            ->get(route('expense-requests.payments.payment-evidence', [
                'expense_request' => $expenseRequest,
                'attachment' => $attachment,
            ]))
            ->assertOk();
    }

    public function test_stranger_cannot_download_payment_evidence(): void
    {
        [, $expenseRequest, , , $attachment] = $this->expenseRequestWithPaymentEvidence();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('expense-requests.payments.payment-evidence', [
                'expense_request' => $expenseRequest,
                'attachment' => $attachment,
            ]))
            ->assertForbidden();
    }

    public function test_cannot_download_foreign_payment_evidence(): void
    {
        [$ownerA, $expenseRequestA, $accounting, , $attachmentA] = $this->expenseRequestWithPaymentEvidence();

        $ownerB = User::factory()->create();
        $expenseRequestB = ExpenseRequest::factory()->create([
            'user_id' => $ownerB->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
            'approved_amount_cents' => 30_000,
        ]);

        $this->actingAs($ownerA)
            ->get(route('expense-requests.payments.payment-evidence', [
                'expense_request' => $expenseRequestB,
                'attachment' => $attachmentA,
            ]))
            ->assertForbidden();

        $this->actingAs($accounting)
            ->get(route('expense-requests.payments.payment-evidence', [
                'expense_request' => $expenseRequestB,
                'attachment' => $attachmentA,
            ]))
            ->assertForbidden();
    }
}
