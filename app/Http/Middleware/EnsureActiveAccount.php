<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gesperrte Konten verlieren ihre laufende Sitzung sofort, nicht erst bei der
 * nächsten Anmeldung (Standard 09: „active = false sperrt“). Läuft für jede
 * Seite der web-Gruppe, auch für das öffentliche Portal.
 *
 * Locked accounts lose their running session right away, not only at the next
 * sign-in (standard 09: "active = false locks"). Runs for every page of the
 * web group, the public portal included.
 */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ! $user->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => __('Dieses Konto ist gesperrt.')]);
        }

        return $next($request);
    }
}
