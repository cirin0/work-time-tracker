<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function __construct(protected AuditLogService $auditLogService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 50);
        $data = $this->auditLogService->getUserLogs(Auth::user(), $perPage);

        return response()->json([
            'message' => 'Audit logs retrieved successfully.',
            'data' => AuditLogResource::collection($data['audit_logs']),
        ]);
    }

    public function all(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->role->value === 'manager') {
            $perPage = $request->input('per_page', 50);
            $data = $this->auditLogService->getCompanyLogs($user->company_id, $perPage);
        } else {
            $perPage = $request->input('per_page', 50);
            $data = $this->auditLogService->getAllLogs($perPage);
        }

        return response()->json([
            'message' => 'Audit logs retrieved successfully.',
            'data' => AuditLogResource::collection($data['audit_logs']),
        ]);
    }
}
