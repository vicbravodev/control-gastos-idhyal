<?php

namespace Tests\Feature;

use App\Enums\ApprovalPolicyDocumentType;
use App\Enums\CombineWithNext;
use App\Enums\ExpenseRequestStatus;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalPolicyStep;
use App\Models\Attachment;
use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseRequestSubmissionAttachmentsHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    private function activeExpenseConcept(): ExpenseConcept
    {
        return ExpenseConcept::factory()->create(['is_active' => true]);
    }

    private function createExpensePolicyWithTwoAndSteps(): void
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

    public function test_store_persists_submission_attachments(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $this->actingAs($requester)
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'concept_description' => 'Con adjuntos',
                'delivery_method' => 'cash',
                'attachments' => [
                    UploadedFile::fake()->create('a.pdf', 80, 'application/pdf'),
                    UploadedFile::fake()->create('b.pdf', 80, 'application/pdf'),
                ],
            ])
            ->assertRedirect();

        $expense = ExpenseRequest::query()->where('user_id', $requester->id)->firstOrFail();
        $this->assertCount(2, $expense->attachments);
        $this->assertSame('a.pdf', $expense->attachments->first()->original_filename);
    }

    public function test_store_rejects_too_many_attachments(): void
    {
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $concept = $this->activeExpenseConcept();

        $files = [];
        for ($i = 0; $i < 11; $i++) {
            $files[] = UploadedFile::fake()->create("f{$i}.pdf", 5, 'application/pdf');
        }

        $this->actingAs($requester)
            ->from(route('expense-requests.create'))
            ->post(route('expense-requests.store'), [
                'requested_amount_cents' => 50_000,
                'expense_concept_id' => $concept->id,
                'delivery_method' => 'cash',
                'attachments' => $files,
            ])
            ->assertRedirect(route('expense-requests.create'))
            ->assertSessionHasErrors('attachments');
    }

    public function test_owner_can_post_additional_attachments_during_approval(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $this->createExpensePolicyWithTwoAndSteps();
        $requester = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $requester->id,
            'status' => ExpenseRequestStatus::ApprovalInProgress,
        ]);

        $this->actingAs($requester)
            ->post(route('expense-requests.submission-attachments.store', $expense), [
                'attachments' => [
                    UploadedFile::fake()->create('extra.pdf', 50, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('expense-requests.show', $expense));

        $this->assertCount(1, $expense->fresh()->attachments);
    }

    public function test_accounting_can_download_submission_attachment(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $owner = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::ApprovalInProgress,
        ]);
        $path = 'expense-request-submissions/'.$expense->id.'/doc.pdf';
        Storage::disk('local')->put($path, 'binary-content');
        $attachment = Attachment::query()->create([
            'attachable_type' => $expense->getMorphClass(),
            'attachable_id' => $expense->id,
            'uploaded_by_user_id' => $owner->id,
            'disk' => 'local',
            'path' => $path,
            'original_filename' => 'doc.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 14,
        ]);

        $accounting = User::factory()->forRole('contabilidad')->create();

        $this->actingAs($accounting)
            ->get(route('expense-requests.submission-attachments.download', [
                'expense_request' => $expense->id,
                'attachment' => $attachment->id,
            ]))
            ->assertOk();
    }

    public function test_owner_cannot_add_submission_attachments_after_payment(): void
    {
        $this->seedRoles();
        $owner = User::factory()->forRole('asesor')->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::AwaitingExpenseReport,
        ]);

        $this->actingAs($owner)
            ->post(route('expense-requests.submission-attachments.store', $expense), [
                'attachments' => [
                    UploadedFile::fake()->create('late.pdf', 10, 'application/pdf'),
                ],
            ])
            ->assertForbidden();
    }

    public function test_owner_can_delete_submission_attachment_before_payment(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::PendingPayment,
        ]);
        $path = 'expense-request-submissions/'.$expense->id.'/x.pdf';
        Storage::disk('local')->put($path, 'x');
        $attachment = Attachment::query()->create([
            'attachable_type' => $expense->getMorphClass(),
            'attachable_id' => $expense->id,
            'uploaded_by_user_id' => $owner->id,
            'disk' => 'local',
            'path' => $path,
            'original_filename' => 'x.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1,
        ]);

        $this->actingAs($owner)
            ->delete(route('expense-requests.submission-attachments.destroy', [
                'expense_request' => $expense->id,
                'attachment' => $attachment->id,
            ]))
            ->assertRedirect(route('expense-requests.show', $expense));

        $this->assertNull(Attachment::query()->find($attachment->id));
        Storage::disk('local')->assertMissing($path);
    }

    public function test_update_adds_attachments_while_submitted(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $expense = ExpenseRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => ExpenseRequestStatus::Submitted,
        ]);

        $this->actingAs($owner)
            ->patch(route('expense-requests.update', $expense), [
                'requested_amount_cents' => $expense->requested_amount_cents,
                'expense_concept_id' => $expense->expense_concept_id,
                'concept_description' => $expense->concept_description,
                'delivery_method' => $expense->delivery_method->value,
                'attachments' => [
                    UploadedFile::fake()->create('from-edit.pdf', 40, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('expense-requests.show', $expense));

        $this->assertCount(1, $expense->fresh()->attachments);
    }
}
