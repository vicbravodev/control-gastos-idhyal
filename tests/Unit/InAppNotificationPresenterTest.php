<?php

namespace Tests\Unit;

use App\Services\Notifications\InAppNotificationPresenter;
use Tests\TestCase;

class InAppNotificationPresenterTest extends TestCase
{
    public function test_presents_known_type_with_expense_request_link(): void
    {
        $presented = InAppNotificationPresenter::present([
            'type' => 'expense_request.paid',
            'expense_request_id' => 12,
            'folio' => 'F-99',
            'amount_cents' => 50_000,
        ]);

        $this->assertSame(12, $presented['expense_request_id']);
        $this->assertNull($presented['vacation_request_id']);
        $this->assertNotSame('', $presented['title']);
        $this->assertNotEmpty($presented['body_lines']);
    }

    public function test_presents_unknown_type_without_crashing(): void
    {
        $presented = InAppNotificationPresenter::present([
            'type' => 'custom.unknown',
            'expense_request_id' => 3,
        ]);

        $this->assertSame(3, $presented['expense_request_id']);
        $this->assertNull($presented['vacation_request_id']);
        $this->assertIsArray($presented['body_lines']);
    }

    public function test_presents_vacation_fully_approved_with_vacation_request_link(): void
    {
        $presented = InAppNotificationPresenter::present([
            'type' => 'vacation_request.fully_approved',
            'vacation_request_id' => 7,
            'folio' => 'V-1',
            'last_approver_name' => 'Ana',
        ]);

        $this->assertSame(7, $presented['vacation_request_id']);
        $this->assertNull($presented['expense_request_id']);
        $this->assertNotEmpty($presented['body_lines']);
    }
}
