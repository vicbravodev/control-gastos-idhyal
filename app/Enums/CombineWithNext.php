<?php

namespace App\Enums;

/**
 * approval_policy_steps.combine_with_next (data-dictionary-stage2).
 */
enum CombineWithNext: string
{
    case And = 'and';

    case Or = 'or';
}
