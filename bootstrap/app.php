<?php

use App\Http\Middleware\AkunAktif;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\WajibGantiPassword;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
        $middleware->web(append: [AkunAktif::class, WajibGantiPassword::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A delete that other records still depend on goes back with a readable notice instead of an error page.
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->isMethod('DELETE') && ! $request->expectsJson() && str_starts_with((string) $e->getCode(), '23')) {
                return back()->with('gagal', 'Data tidak bisa dihapus karena masih dipakai oleh data lain.');
            }
        });

        // An expired form (CSRF token mismatch) goes back to the form with what was typed, minus passwords.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && ! $request->expectsJson()) {
                return back()->withInput($request->except('password', 'password_confirmation', 'current_password', '_token'))
                    ->with('peringatan', 'Halaman sudah terlalu lama dibuka. Silakan kirim ulang formulirnya.');
            }
        });
    })->create();
