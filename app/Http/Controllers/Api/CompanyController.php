<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Services\CompanyService;

class CompanyController extends Controller
{
    public function __construct(protected CompanyService $companyService)
    {
    }

    public function show(): CompanyResource
    {
        $data = $this->companyService->getCompany();

        return new CompanyResource($data['company']);
    }
}
