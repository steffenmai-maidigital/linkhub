<?php

namespace App\Http\Middleware;

use App\Support\Mfa;
use Closure;
use Illuminate\Http\Request;

/**
 * Ist der zweite Faktor für die Gruppe Pflicht und noch nicht eingerichtet,
 * geht es nur auf die Konto-Seite und zum Abmelden (Standard 09). Die Stufe
 * wird bei jeder Anfrage neu bestimmt: die strengere aus der Anmeldung und
 * der aktuellen Richtlinie. Wird jemand Manager oder die Richtlinie strenger,
 * gilt die Pflicht sofort, nicht erst bei der nächsten Anmeldung.
 *
 * If the second factor is mandatory for the group and not yet set up,
 * only the account page and sign-out are reachable (Standard 09). The level
 * is determined anew on every request: the stricter one of the sign-in and
 * the current policy. If someone becomes a manager or the policy gets
 * stricter, the requirement applies at once, not only at the next sign-in.
 */
class EnsureMfa
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            $pending = self::policy($request) === Mfa::REQUIRED && ! $user->hasTwoFactor();
            // die Konto-Seite zeigt damit den Hinweis / the account page shows the notice with it
            $request->session()->put('mfa_pending', $pending);
            if ($pending && ! $request->routeIs('account', 'account.*', 'logout')) {
                return redirect()->route('account');   // die Konto-Seite erklärt, warum / the account page explains why
            }
        }

        return $next($request);
    }

    /**
     * Wirksame Stufe dieser Sitzung: die strengere aus der Anmeldung und der
     * aktuellen Richtlinie (mit den Verzeichnisgruppen der Anmeldung)
     *
     * Effective level of this session: the stricter one of the sign-in and the
     * current policy (with the directory groups of the sign-in)
     */
    public static function policy(Request $request): string
    {
        $session = $request->session();
        $signedIn = (string) $session->get('mfa_policy', Mfa::OPTIONAL);
        if (! isset(Mfa::LEVELS[$signedIn])) {
            $signedIn = Mfa::OPTIONAL;
        }

        return Mfa::stricter($signedIn, Mfa::policy($request->user(), (array) $session->get('mfa_groups', [])));
    }
}
