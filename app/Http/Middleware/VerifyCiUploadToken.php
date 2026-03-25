<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCiUploadToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string)config('services.app_release.ci_upload_token', '');
        $providedToken = (string)$request->header('X-CI-Upload-Token', '');

        if ($configuredToken === '' || !hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'message' => 'Unauthorized CI upload token.',
            ], 401);
        }

        return $next($request);
    }
}

