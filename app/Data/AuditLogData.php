<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AuditLog')]
class AuditLogData extends Data
{
    public function __construct(
        public int     $id,
        public ?int    $user_id,
        public ?string $user_name,
        public ?string $user_email,
        public string  $action,
        public ?string $model_type,
        public ?int    $model_id,
        public ?array  $old_values,
        public ?array  $new_values,
        public ?string $ip_address,
        public ?string $user_agent,
        public string  $created_at,
        public string  $updated_at,
        #[Computed]
        public ?string $model_name,
    )
    {
    }

    public static function fromModel($auditLog): self
    {
        return new self(
            id: $auditLog->id,
            user_id: $auditLog->user_id,
            user_name: $auditLog->user?->name,
            user_email: $auditLog->user?->email,
            action: $auditLog->action,
            model_type: $auditLog->model_type,
            model_id: $auditLog->model_id,
            old_values: $auditLog->old_values,
            new_values: $auditLog->new_values,
            ip_address: $auditLog->ip_address,
            user_agent: $auditLog->user_agent,
            created_at: $auditLog->created_at->toIso8601String(),
            updated_at: $auditLog->updated_at->toIso8601String(),
            model_name: $auditLog->model_type ? class_basename($auditLog->model_type) : null,
        );
    }
}
