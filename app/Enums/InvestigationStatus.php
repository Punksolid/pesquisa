<?php

namespace App\Enums;

enum InvestigationStatus: string
{
    case Open = 'open';
    case Solved = 'solved';
    case Archived = 'archived';
}
