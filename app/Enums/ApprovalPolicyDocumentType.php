<?php

namespace App\Enums;

/**
 * approval_policies.document_type examples from data-dictionary-stage2.
 */
enum ApprovalPolicyDocumentType: string
{
    case ExpenseRequest = 'expense_request';

    case VacationRequest = 'vacation_request';
}
