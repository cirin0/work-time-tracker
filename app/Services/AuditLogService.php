<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\AuditLogRepository;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function __construct(protected AuditLogRepository $auditLogRepository)
    {
    }

    public function logModelCreated(Model $model, ?User $user = null, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        return $this->log(
            action: 'created',
            user: $user,
            model: $model,
            newValues: $model->getAttributes(),
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );
    }

    public function log(
        string  $action,
        ?User   $user = null,
        ?Model  $model = null,
        ?array  $oldValues = null,
        ?array  $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array
    {
        $auditLog = $this->auditLogRepository->create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return ['audit_log' => $auditLog];
    }

    public function logModelUpdated(Model $model, array $oldValues, ?User $user = null, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        return $this->log(
            action: 'updated',
            user: $user,
            model: $model,
            oldValues: $oldValues,
            newValues: $model->getChanges(),
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );
    }

    public function logModelDeleted(Model $model, ?User $user = null, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        return $this->log(
            action: 'deleted',
            user: $user,
            model: $model,
            oldValues: $model->getAttributes(),
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );
    }

    public function logAction(string $action, ?User $user = null, ?string $ipAddress = null, ?string $userAgent = null, ?array $data = null): array
    {
        return $this->log(
            action: $action,
            user: $user,
            newValues: $data,
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );
    }

    public function getUserLogs(User $user, int $perPage = 50): array
    {
        $logs = $this->auditLogRepository->getAllForUser($user, $perPage);

        return ['audit_logs' => $logs];
    }

    public function getModelLogs(Model $model, int $perPage = 50): array
    {
        $logs = $this->auditLogRepository->getAllForModel(get_class($model), $model->id, $perPage);

        return ['audit_logs' => $logs];
    }

    public function getAllLogs(int $perPage = 50): array
    {
        $logs = $this->auditLogRepository->getAll($perPage);

        return ['audit_logs' => $logs];
    }

    public function getCompanyLogs(int $companyId, int $perPage = 50): array
    {
        $logs = $this->auditLogRepository->getAllForCompany($companyId, $perPage);

        return ['audit_logs' => $logs];
    }

    public function cleanupOldLogs(int $daysToKeep = 90): array
    {
        $date = now()->subDays($daysToKeep);
        $deletedCount = $this->auditLogRepository->deleteOlderThan($date);

        return [
            'deleted_count' => $deletedCount,
            'message' => "Deleted {$deletedCount} audit log(s) older than {$daysToKeep} days.",
        ];
    }
}
