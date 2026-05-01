<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Hello, this is the API v2 TestController index method.',
        ]);
    }
}
