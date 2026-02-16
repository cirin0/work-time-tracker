<?php

namespace App\Repositories;

use App\Models\AuditLog;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditLogRepository
{
    public function find(int $id): ?AuditLog
    {
        return AuditLog::query()
            ->with('user:id,name,email')
            ->find($id);
    }

    public function create(array $data): AuditLog
    {
        return AuditLog::query()->create($data);
    }

    public function getAllForUser(User $user, int $perPage = 50): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('user:id,name,email')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getAllForModel(string $modelType, int $modelId, int $perPage = 50): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('user:id,name,email')
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getAll(int $perPage = 50): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getByAction(string $action, int $perPage = 50): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('user:id,name,email')
            ->where('action', $action)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getAllForCompany(int $companyId, int $perPage = 50): LengthAwarePaginator
    {
        return AuditLog::query()
            ->whereHas('user', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with('user:id,name,email,company_id')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function deleteOlderThan(DateTimeInterface $date): int
    {
        return AuditLog::query()
            ->where('created_at', '<', $date)
            ->delete();
    }
}
