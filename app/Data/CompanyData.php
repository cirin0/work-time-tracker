<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Company')]
class CompanyData extends Data
{
    public function __construct(
        public int     $id,
        public string  $name,
        public ?string $email,
        public ?string $phone,
        public ?string $logo,
        public ?string $description,
        public ?string $address,
    )
    {
    }
}
