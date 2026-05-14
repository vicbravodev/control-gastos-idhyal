<?php

namespace Database\Factories;

use App\Enums\ExpenseReportDocumentType;
use App\Enums\ExpenseReportStatus;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseReport>
 */
class ExpenseReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_request_id' => ExpenseRequest::factory(),
            'status' => ExpenseReportStatus::Draft,
            'reported_amount_cents' => fake()->randomElement([10_000, 50_000, 100_000]),
            'document_type' => ExpenseReportDocumentType::Factura,
            'label' => null,
            'reviewer_user_id' => null,
            'reviewed_at' => null,
            'submitted_at' => null,
        ];
    }
}
