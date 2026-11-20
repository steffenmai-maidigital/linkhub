<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession as LaravelAuthenticateSession;
use Illuminate\Support\Facades\Auth;

/**
 * Passwortwechsel beendet die anderen Sitzungen: Laravels AuthenticateSession
 * merkt sich in jeder Sitzung einen Fingerabdruck des Passwort-Hashes
 * (HMAC mit dem APP_KEY) und meldet ab, sobald der Hash in der Datenbank nicht
 * mehr passt — nach dem eigenen Wechsel, „Passwort zurücksetzen“ durch einen
 * Administrator oder dem Notzugang. Verzeichniskonten haben kein Passwort in
 * LinkHub und sind nicht betroffen. Hier kommen nur der Hinweis auf der
 * Anmeldeseite und die gewählte Sprache dazu; keep() zieht beim eigenen
 * Wechsel die laufende Sitzung nach.
 *
 * A password change ends the other sessions: Laravel's AuthenticateSession
 * keeps a fingerprint of the password hash (HMAC with the APP_KEY) in every
 * session and signs out as soon as the hash in the database no longer
 * matches — after one's own change, "reset password" by an administrator or
 * emergency access. Directory accounts have no password in LinkHub and are not
 * affected. This class only adds the notice on the sign-in page and keeps the
 * chosen language; keep() updates the running session on one's own change.
 */
class AuthenticateSession extends LaravelAuthenticateSession
{
    /**
     * Abmelden wie Laravel, dazu der Hinweis für die Anmeldeseite. Nur ein
     * Merkzeichen in der Sitzung: übersetzt wird erst in der Ansicht, wenn die
     * Sprache feststeht.
     *
     * Sign out like Laravel, plus the notice for the sign-in page. Only a
     * marker in the session: it is translated in the view, once the language
     * is set.
     */
    protected function logout($request)
    {
        $locale = $request->session()->get('locale');
        try {
            parent::logout($request);
        } catch (AuthenticationException $e) {
            // Die Sitzung ist geleert; die gewählte Sprache bleibt / the session is emptied; the chosen language stays
            if ($locale) {
                $request->session()->put('locale', $locale);
            }
            $request->session()->flash('session_ended', true);

            throw $e;
        }
    }

    /**
     * Eigene Sitzung beim eigenen Passwortwechsel behalten: neuen Hash in die
     * Sitzung schreiben und das Konto im Guard austauschen (sonst merkt sich die
     * Middleware am Ende der Anfrage noch den alten Hash).
     *
     * Keep one's own session on one's own password change: write the new hash
     * into the session and replace the account in the guard (otherwise the
     * middleware would still store the old hash at the end of the request).
     */
    public static function keep(Request $request, User $user): void
    {
        Auth::setUser($user);
        $request->session()->put(
            'password_hash_' . Auth::getDefaultDriver(),
            Auth::guard()->hashPasswordForCookie($user->getAuthPassword()),
        );
    }
}
