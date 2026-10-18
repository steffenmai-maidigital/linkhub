<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Mfa;
use App\Support\Directory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Anmeldung (Standard 09): zuerst eigene Konten, dann — wenn eingeschaltet —
 * das Verzeichnis (Active Directory / LDAP). Verzeichniskonten entstehen bei
 * der ersten Anmeldung und werden über die objectGUID wiedererkannt (auch
 * nach einer Umbenennung); die Rollen Admin/Manager kommen aus den Gruppen.
 * Danach ggf. der zweite Faktor (TOTP) nach der Richtlinie je Gruppe.
 *
 * Sign-in (Standard 09): own accounts first, then — if switched on —
 * the directory (Active Directory / LDAP). Directory accounts are created on
 * their first sign-in and are recognised via the objectGUID (also after a
 * rename); the admin/manager roles come from the groups.
 * After that, if required, the second factor (TOTP) per the policy of each group.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login', ['directory' => Directory::enabled()]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
        ]);
        $name = mb_strtolower(trim($data['username']));

        // Bremse gegen Passwort-Raten je Benutzername, unabhängig von der
        // IP-Adresse: 10 Fehlversuche → 30 Minuten gesperrt (Standard 06)
        // Throttle password guessing per username, independent of the
        // IP address: 10 failed attempts → locked for 30 minutes (Standard 06)
        $throttle = 'sign-in:' . $name;
        if (RateLimiter::tooManyAttempts($throttle, 10)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttle) / 60);

            return back()->withErrors(['username' => __('Zu viele Fehlversuche für diesen Benutzernamen. Bitte in :minuten Minuten erneut versuchen.', ['minuten' => $minutes])])->onlyInput('username');
        }
        $fail = function (string $message) use ($throttle) {
            RateLimiter::hit($throttle, 1800);

            return back()->withErrors(['username' => $message])->onlyInput('username');
        };

        $user = User::where('username', $name)->first();
        $groups = [];

        if ($user && $user->isLocal()) {
            if (! $user->password || ! Hash::check($data['password'], $user->password)) {
                return $fail(__('Anmeldung fehlgeschlagen.'));
            }
        } else {
            $directory = Directory::fromSettings();
            if (! $directory) {
                return $fail(__('Anmeldung fehlgeschlagen.'));
            }
            try {
                $entry = $directory->authenticate($name, $data['password']);
            } catch (\Throwable $e) {
                report($e);

                return back()->withErrors(['username' => __('Das Verzeichnis ist gerade nicht erreichbar. Bitte später erneut versuchen oder den Administrator informieren.')])->onlyInput('username');
            }
            if (! $entry) {
                return $fail(__('Anmeldung fehlgeschlagen.'));
            }
            $user = $this->directoryUser($entry, $entry['username'] ?: $name);
            if (! $user) {
                return $fail(__('Anmeldung fehlgeschlagen.'));
            }
            $user->fill([
                'name'        => $entry['name'] ?: $name,
                'email'       => $entry['mail'],
                'auth_source' => User::DIRECTORY,
                'active'      => $user->exists ? $user->active : true,
            ])->save();
            $groups = $entry['groups'];
            $user->permissionsFromGroups($groups);
        }

        if (! $user->active) {
            return $fail(__('Dieses Konto ist gesperrt.'));
        }
        RateLimiter::clear($throttle);

        // Zweiter Faktor nach Richtlinie: eingerichtet → Code abfragen
        // Second factor per policy: set up → ask for the code
        $policy = Mfa::policy($user, $groups);
        if ($policy !== Mfa::OFF && $user->hasTwoFactor()) {
            $request->session()->put('login.2fa', [
                'id' => $user->id, 'until' => now()->addMinutes(5)->timestamp,
                'groups' => $groups, 'policy' => $policy, 'remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('login.code');
        }

        return $this->signIn($request, $user, $groups, $policy, $request->boolean('remember'));
    }

    /**
     * Konto zu einem Verzeichniseintrag (noch nicht gespeichert, wenn neu).
     * Zuerst über die unveränderliche Kennung (AD-objectGUID bzw. entryUUID):
     * wurde das Konto im Verzeichnis umbenannt, wird der Benutzername hier
     * nachgezogen — Rechte und zweiter Faktor bleiben am selben Konto. Ohne
     * Treffer über den Benutzernamen; die Kennung wird dann gespeichert.
     * Eigene Konten tragen nie eine Kennung. null = Anmeldung ablehnen.
     *
     * Account for a directory entry (not saved yet if new). First via the
     * immutable id (AD objectGUID or entryUUID): if the account was renamed in
     * the directory, the user name is updated here — roles and the second
     * factor stay with the same account. Without a match via the user name;
     * the id is stored then. Own accounts never carry an id. null = refuse.
     */
    private function directoryUser(array $entry, string $username): ?User
    {
        $guid = $entry['guid'] ?? null;
        $user = $guid ? User::where('guid', $guid)->where('auth_source', User::DIRECTORY)->first() : null;

        if ($user) {
            if ($user->username !== $username) {
                // Neuer Name schon vergeben (eigenes oder anderes Konto): ablehnen
                // New name already taken (own or another account): refuse
                if (User::where('username', $username)->whereKeyNot($user->id)->exists()) {
                    Log::warning('Verzeichnis-Anmeldung abgelehnt: umbenanntes Konto, neuer Benutzername schon vergeben / directory sign-in refused: renamed account, new user name already taken',
                        ['id' => $user->id, 'old' => $user->username, 'new' => $username]);

                    return null;
                }
                activity('Auth')->causedBy($user)->performedOn($user)
                    ->withProperties(['old' => $user->username, 'new' => $username])
                    ->log('Benutzername aus dem Verzeichnis übernommen');
                $user->username = $username;
            }

            return $user;
        }

        $user = User::firstOrNew(['username' => $username]);
        if ($user->exists && $user->isLocal()) {
            // eigenes Konto mit gleichem Namen hat Vorrang
            // an own account with the same name takes precedence
            return null;
        }
        if ($user->exists && $guid && $user->guid && $user->guid !== $guid) {
            // Gleicher Name, aber anderes Verzeichniskonto (gelöscht und neu
            // angelegt): nicht die Rechte und den zweiten Faktor des alten erben
            // Same name, but another directory account (deleted and created
            // again): do not inherit the roles and second factor of the old one
            Log::warning('Verzeichnis-Anmeldung abgelehnt: Benutzername gehört zu einem anderen Verzeichniskonto (objectGUID) / directory sign-in refused: user name belongs to another directory account (objectGUID)',
                ['id' => $user->id, 'username' => $username]);

            return null;
        }
        if ($guid) {
            $user->guid = $guid;
        }

        return $user;
    }

    public function showTwoFactor(Request $request)
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.code');
    }

    public function twoFactor(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:20']]);
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['username' => __('Bitte melden Sie sich erneut an.')]);
        }

        $key = '2fa:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $request->session()->forget('login.2fa');

            return redirect()->route('login')->withErrors(['username' => __('Zu viele falsche Codes. Bitte später erneut anmelden.')]);
        }
        if (! $user->verifyTwoFactor($request->input('code'))) {
            RateLimiter::hit($key, 300);

            return back()->withErrors(['code' => __('Der Code stimmt nicht.')]);
        }
        RateLimiter::clear($key);

        $p = $request->session()->pull('login.2fa');

        return $this->signIn($request, $user, $p['groups'] ?? [], $p['policy'] ?? Mfa::OPTIONAL, (bool) ($p['remember'] ?? false));
    }

    /**
     * Sitzung aufbauen — nach Passwort bzw. Verzeichnis und ggf. zweitem Faktor
     *
     * Establish the session — after password or directory and, if required, the second factor
     */
    private function signIn(Request $request, User $user, array $groups, string $policy, bool $remember)
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('mfa_policy', $policy);
        // Pflicht ohne eingerichteten zweiten Faktor: erst einrichten (Middleware mfa)
        // Mandatory without a second factor set up: set it up first (middleware mfa)
        $request->session()->put('mfa_pending', $policy === Mfa::REQUIRED && ! $user->hasTwoFactor());
        $user->forceFill(['last_login_at' => now()])->save();

        activity('Auth')->causedBy($user)
            ->withProperties($groups ? ['groups' => array_map([Directory::class, 'cn'], $groups)] : [])
            ->log($user->isLocal() ? 'Anmeldung (eigenes Konto)' : 'Anmeldung (Verzeichnis)');

        return redirect()->intended(route('home'));
    }

    private function pendingUser(Request $request): ?User
    {
        $p = $request->session()->get('login.2fa');
        if (! $p || ($p['until'] ?? 0) < time()) {
            return null;
        }

        return User::find($p['id']);
    }

    public function logout(Request $request)
    {
        activity('Auth')->causedBy($request->user())->log('Abmeldung');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
