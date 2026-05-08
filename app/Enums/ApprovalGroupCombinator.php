<?php

namespace App\Enums;

enum ApprovalGroupCombinator: string
{
    case AnyOf = 'any_of';

    case AllOf = 'all_of';
}
