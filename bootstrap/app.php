<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Broadcast;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Illuminate\Foundation\Configuration\Exceptions $exceptions) {
        $exceptions->render(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e, $request) {
            return response()->json(['error' => 'Token has expired'], 401);
        });

        $exceptions->render(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $e, $request) {
            return response()->json(['error' => 'Token is invalid'], 401);
        });

        $exceptions->render(function (Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException $e, $request) {
            return response()->json(['error' => $e->getMessage()], 401);
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
//        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
//            if ($request->is('api/*')) {
//                return response()->json([
//                    'error' => 'Resource or user not found',
//                ], 404);
//            }
//        });
//        $exceptions->render(function (BadRequestHttpException $e, Request $request) {
//            if ($request->is('api/*')) {
//                return response()->json([
//                    'error' => 'Bad request',
//                    'message' => $e->getMessage(),
//                ], 400);
//            }
//        });
//        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
//            if ($request->is('api/*')) {
//                return response()->json([
//                    'error' => 'Access denied',
//                    'message' => $e->getMessage(),
//                ], 403);
//            }
//        });
//        $exceptions->render(function (AuthenticationException $e, Request $request) {
//            if ($request->is('api/*')) {
//                return response()->json([
//                    'error' => 'Unauthorized',
//                ], 401);
//            }
//        });
//        $exceptions->render(function (Exception $e, Request $request) {
//            if ($request->is('api/*')) {
//                return response()->json([
//                    'error' => 'Internal server error',
//                    'message' => $e->getMessage(),
//                ], 500);
//            }
//        });
    })
    ->withBroadcasting(Broadcast::class, attributes: [
        'guards' => ['api'],
    ])
    ->create();
