<?php

namespace App\Enums;

enum HypothesisStatus: string
{
    case Proposed = 'proposed';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
}
