<?php

namespace App\Enums;

enum WorkMode: string
{
    case REMOTE = 'remote';
    case OFFICE = 'office';
    case HYBRID = 'hybrid';
}
