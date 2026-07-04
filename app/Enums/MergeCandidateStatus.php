<?php

namespace App\Enums;

enum MergeCandidateStatus: string
{
    case Pending = 'pending';
    case Dismissed = 'dismissed';
    case Merged = 'merged';
}
