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
                ipAddress: self::getClientIp($request),
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

    protected static function getClientIp($request): ?string
    {
        if (!$request) {
            return null;
        }

        // Check for X-Forwarded-For header (used by most proxies/load balancers)
        if ($request->header('X-Forwarded-For')) {
            $ips = explode(',', $request->header('X-Forwarded-For'));
            // Get the first IP (original client IP)
            return trim($ips[0]);
        }

        // Check for X-Real-IP header (used by some proxies like nginx)
        if ($request->header('X-Real-IP')) {
            return $request->header('X-Real-IP');
        }

        // Check for CF-Connecting-IP (Cloudflare)
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }

        // Fallback to Laravel's ip() method
        return $request->ip();
    }
}
