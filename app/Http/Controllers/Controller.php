<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'API for managing users, including registration, login, and role management.',
    title: 'User Management API'
)]
abstract class Controller
{
    //
}
