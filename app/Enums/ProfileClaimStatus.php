<?php

namespace App\Enums;

enum ProfileClaimStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
