<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class AdminSchedulerController extends Controller
{
    public function sendWarnings(): JsonResponse
    {
        try {
            Artisan::call('work-sessions:send-warnings', [
                '--warning-minutes' => 30,
            ]);

            $output = Artisan::output();

            return response()->json([
                'message' => 'Warnings sent successfully',
                'output' => trim($output),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send warnings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function autoCloseSessions(): JsonResponse
    {
        try {
            Artisan::call('work-sessions:auto-close', [
                '--hours' => 5,
            ]);

            $output = Artisan::output();

            return response()->json([
                'message' => 'Sessions closed successfully',
                'output' => trim($output),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to close sessions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getStatus(): JsonResponse
    {
        try {
            $sessionsToClose = TimeEntry::query()
                ->whereNull('stop_time')
                ->where('start_time', '<=', now()->subHours(5))
                ->count();

            $sessionsToWarn = TimeEntry::query()
                ->whereNull('stop_time')
                ->where('start_time', '<=', now()->subHours(4)->subMinutes(30))
                ->where('start_time', '>', now()->subHours(5))
                ->count();

            return response()->json([
                'sessions_to_close' => $sessionsToClose,
                'sessions_to_warn' => $sessionsToWarn,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cleanupAuditLogs(): JsonResponse
    {
        try {
            Artisan::call('audit-logs:cleanup', [
                '--days' => 90,
            ]);

            $output = Artisan::output();

            return response()->json([
                'message' => 'Audit logs cleaned up successfully',
                'output' => trim($output),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cleanup audit logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

