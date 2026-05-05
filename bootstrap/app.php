<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpFoundation\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('static')
                ->group(base_path('routes/static.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => App\Http\Middleware\RoleMiddleware::class,
            'ci.upload' => App\Http\Middleware\VerifyCiUploadToken::class,
        ]);

        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->group('static', [
            \App\Http\Middleware\SetCacheControlHeader::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Illuminate\Foundation\Configuration\Exceptions $exceptions) {
        $exceptions->render(function (TokenExpiredException $e, $request) {
            return response()->json(['error' => 'Token has expired'], 401);
        });

        $exceptions->render(function (TokenInvalidException $e, $request) {
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
    ->withSchedule(function (Schedule $schedule) {
        // Send warnings 30 minutes before auto-close
        $schedule->command('work-sessions:send-warnings --warning-minutes=30')
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        // Auto-close sessions after 5 hours
        $schedule->command('work-sessions:auto-close --hours=5')
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        // Cleanup old audit logs (existing)
        $schedule->command('audit-logs:cleanup --days=90')
            ->daily()
            ->at('02:00');
    })
    ->create();
