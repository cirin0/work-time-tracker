<?php

namespace App\Services;

use App\Models\Company;

class QrCodeValidator
{
    public function verify(Company $company, string $qrCode): bool
    {
        if (!$company->qr_secret) {
            return false;
        }

        $expectedCode = hash('sha256', $company->qr_secret . date('Y-m-d'));

        return hash_equals($expectedCode, $qrCode);
    }
}
