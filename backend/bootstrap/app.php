<?php

use App\Http\Middleware\EnsureStaffAccess;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ThrottleEmbedLeadIntake;
use App\Http\Middleware\ThrottleStaffLogin;
use App\Http\Middleware\ValidateEmbedOrigin;
use App\Http\Middleware\ValidateEmbedSiteKey;
use App\Support\Api\ApiProblem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('pipeline:midnight')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('royalties:calculate-due')->dailyAt('00:30')->withoutOverlapping();
        $schedule->command('ach:process-due-transfers')->dailyAt('00:50')->withoutOverlapping();
        $schedule->command('areas:calculate-royalties')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('notifications:process-scheduled')->hourly()->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'staff' => EnsureStaffAccess::class,
            'embed.site_key' => ValidateEmbedSiteKey::class,
            'embed.origin' => ValidateEmbedOrigin::class,
            'throttle.staff_login' => ThrottleStaffLogin::class,
            'throttle.embed_lead_intake' => ThrottleEmbedLeadIntake::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiProblem::unauthenticated($exception->getMessage());
            }

            return null;
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiProblem::forbidden($exception->getMessage() ?: 'Forbidden.');
            }

            return null;
        });
    })->create();
