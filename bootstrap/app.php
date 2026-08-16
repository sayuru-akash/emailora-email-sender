<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreventSearchIndexing;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/email/resend',
            'webhooks/email/brevo',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            PreventSearchIndexing::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();

            if ($request->expectsJson() || $request->is('webhooks/*')) {
                return $response;
            }

            if (! in_array($status, [403, 404, 405, 419, 429, 500, 503], true)) {
                return $response;
            }

            // Keep local stack traces for unexpected server errors, but always render safe HTTP errors.
            if ($status >= 500 && config('app.debug')) {
                return $response;
            }

            return Inertia::render('errors/ErrorPage', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status)
                ->header('X-Robots-Tag', PreventSearchIndexing::ROBOTS_DIRECTIVE);
        });
    })->create();
