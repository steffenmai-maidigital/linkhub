<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Startpasswort zuerst ändern (Standard 09): Solange ein eigenes Konto noch
 * das Passwort hat, das LinkHub oder ein Administrator vergeben hat, geht es
 * nur auf die Konto-Seite und zum Abmelden — auch nicht auf die Hilfe. Gilt
 * für den ersten Administrator mit Zufallspasswort, für neu angelegte Konten,
 * nach „Passwort zurücksetzen“ und nach dem Notzugang. Nicht für
 * Verzeichniskonten und nicht im Demo-Modus (User::mustChangePassword()).
 *
 * Change the initial password first (Standard 09): as long as an own account
 * still has the password LinkHub or an administrator handed out, only the
 * account page and sign-out are reachable — not even the help page. Applies
 * to the first administrator with a random password, to newly created
 * accounts, after "reset password" and after emergency access. Not for
 * directory accounts and not in demo mode (User::mustChangePassword()).
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user?->mustChangePassword() && ! $request->routeIs('account', 'account.*', 'logout')) {
            return redirect()->route('account');   // die Konto-Seite erklärt, warum / the account page explains why
        }

        return $next($request);
    }
}
