<?php

namespace App\Data;

use App\Enums\UserRole;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AuthUser')]
class AuthUserData extends Data
{
    public function __construct(
        public int      $id,
        public string   $name,
        public string   $email,
        public UserRole $role,
        public ?string  $avatar,
        public string   $created_at,
        public string   $updated_at,
    )
    {
    }
}
