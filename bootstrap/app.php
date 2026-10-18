<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Proxy-Angaben (echte Absender-IP, HTTPS) nur aus internen Netzen
        // glauben — sonst könnte jeder eine falsche IP vortäuschen.
        // Mit TRUSTED_PROXIES anpassbar ("*" = allen vertrauen).
        // Only trust proxy details (real sender IP, HTTPS) from internal networks
        // — otherwise anyone could fake an IP address.
        // Adjustable via TRUSTED_PROXIES ("*" = trust all).
        $proxies = env('TRUSTED_PROXIES', '127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,::1,fc00::/7');
        $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));

        // Sicherheits-Header und sichere Cookies für jede Antwort (Standard 06)
        // Security headers and secure cookies for every response (Standard 06)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            // Merkt den Anlass einer Aktion fürs passende Lob von Rua / remembers the occasion of an action for Rua's fitting praise
            \App\Http\Middleware\FoxOccasion::class,
        ]);

        // Sprache vor dem Laden der Modelle aus der URL setzen — sonst erscheint
        // die 404-Seite für nicht gefundene Einträge in der falschen Sprache
        // Set the locale before route model binding — otherwise the 404 page
        // for missing records appears in the wrong language
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\SetLocale::class,
        );

        $middleware->alias([
            'admin'   => EnsureAdmin::class,
            'manager' => EnsureManager::class,
            'mfa'     => \App\Http\Middleware\EnsureMfa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Fehlernummer in jeden Protokolleintrag; dieselbe zeigt die Fehlerseite (Standard 03)
        // Error reference in every log entry; the error page shows the same one (Standard 03)
        $exceptions->context(fn () => ['reference' => \App\Support\ErrorPage::reference()]);
    })->create();
