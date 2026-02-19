<?php

namespace App\Traits;

use App\Models\User;
use App\Services\AuditLogService;
use Exception;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            self::logModelEvent($model, 'created', null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            if ($model->wasChanged()) {
                $oldValues = array_intersect_key($model->getOriginal(), $model->getChanges());
                $newValues = $model->getChanges();
                self::logModelEvent($model, 'updated', $oldValues, $newValues);
            }
        });

        static::deleting(function (Model $model) {
            self::logModelEvent($model, 'deleted', $model->getAttributes(), null);
        });
    }

    protected static function logModelEvent(Model $model, string $action, ?array $oldValues, ?array $newValues): void
    {
        try {
            $auditLogService = app(AuditLogService::class);
            $user = auth()->user();
            $request = request();

            $auditLogService->log(
                action: $action,
                user: $user instanceof User ? $user : null,
                model: $model,
                oldValues: $oldValues,
                newValues: $newValues,
                ipAddress: $request?->ip(),
                userAgent: $request?->userAgent()
            );
        } catch (Exception $e) {
            logger()->error('Failed to log audit event', [
                'model' => get_class($model),
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
