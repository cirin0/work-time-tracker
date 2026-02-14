<?php

namespace App\Enums;

enum WorkMode: string
{
    case remote = 'remote';
    case office = 'office';
    case hybrid = 'hybrid';
}
