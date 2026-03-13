<?php

namespace App\Enums;

enum LeaveRequestType: string
{
    case SICK = 'sick';
    case VACATION = 'vacation';
    case UNPAID = 'unpaid';
    case PERSONAL = 'personal';
    case BUSINESS_TRIP = 'business_trip';
}
