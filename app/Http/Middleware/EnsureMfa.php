<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Ist der zweite Faktor für die Gruppe Pflicht und noch nicht eingerichtet,
 * geht es nur auf die Konto-Seite und zum Abmelden (Standard 09).
 *
 * If the second factor is mandatory for the group and not yet set up,
 * only the account page and sign-out are reachable (Standard 09).
 */
class EnsureMfa
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->session()->get('mfa_pending') && ! $request->routeIs('account', 'account.*', 'logout')) {
            return redirect()->route('account');   // die Konto-Seite erklärt, warum / the account page explains why
        }

        return $next($request);
    }
}
