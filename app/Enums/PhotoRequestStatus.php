<?php

namespace App\Enums;

enum PhotoRequestStatus: string
{
    case Pending   = 'pending';
    case Fulfilled = 'fulfilled';
    case Closed    = 'closed';
}
