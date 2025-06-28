<?php

namespace App\Enums;

enum UserRole: string
{
    case EMPLOYEE = 'employee';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
}
