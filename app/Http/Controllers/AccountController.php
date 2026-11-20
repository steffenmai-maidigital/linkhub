<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthenticateSession;
use App\Http\Middleware\EnsureMfa;
use App\Models\Setting;
use App\Models\User;
use App\Support\Mfa;
use App\Support\Totp;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Eigenes Konto: Passwort (eigene Konten), Zwei-Faktor-Anmeldung
 *
 * Own account: password (own accounts), two-factor sign-in
 */
class AccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $setup = null;

        // Einrichtung läuft: Geheimnis liegt (noch unbestätigt) in der Sitzung
        // Setup in progress: the secret sits (not yet confirmed) in the session
        if ($secret = $request->session()->get('2fa.secret')) {
            $uri = Totp::uri($secret, $user->username, (Setting::get('brand_company_name') ?: 'LinkHub') . ' LinkHub');
            $svg = (new Writer(new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd())))->writeString($uri);
            $setup = ['secret' => trim(chunk_split($secret, 4, ' ')), 'qr' => $svg];
        }

        return view('account', [
            'user'       => $user,
            'setup'      => $setup,
            'codes'      => $request->session()->get('2fa.codes'),
            'policy'     => $this->policy($request),
            'mfaPending' => (bool) $request->session()->get('mfa_pending', false),
            'mustChangePassword' => $user->mustChangePassword(),
        ]);
    }

    /**
     * Wirksame Stufe wie in der Middleware: die strengere aus Anmeldung und aktueller Richtlinie
     *
     * Effective level as in the middleware: the stricter one of sign-in and current policy
     */
    private function policy(Request $request): string
    {
        return EnsureMfa::policy($request);
    }

    public function password(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isLocal(), 403, 'Verzeichniskonten ändern ihr Passwort im Verzeichnis.');
        $data = $request->validate([
            'current'  => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ]);
        if (! Hash::check($data['current'], $user->password)) {
            return back()->withErrors(['current' => __('Das bisherige Passwort stimmt nicht.')]);
        }
        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => __('Bitte ein neues Passwort wählen.')]);
        }
        $user->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();
        // Diese Sitzung bleibt, alle anderen dieses Kontos enden beim nächsten Seitenaufruf
        // This session stays, all others of this account end at their next page view
        AuthenticateSession::keep($request, $user);
        activity('Benutzer')->causedBy($user)->log('Eigenes Passwort geändert');

        return redirect()->route('account')->with('ok', __('Passwort geändert. Auf anderen Geräten sind Sie mit diesem Konto jetzt abgemeldet.'));
    }

    public function startTwoFactor(Request $request)
    {
        abort_unless($this->policy($request) !== Mfa::OFF, 403, 'Zwei-Faktor ist für diese Gruppe ausgeschaltet.');
        $request->session()->put('2fa.secret', Totp::newSecret());

        return redirect()->route('account');
    }

    public function confirmTwoFactor(Request $request)
    {
        // wie beim Start: bei Stufe „aus“ kein zweiter Faktor / as at the start: no second factor at level "off"
        abort_unless($this->policy($request) !== Mfa::OFF, 403, 'Zwei-Faktor ist für diese Gruppe ausgeschaltet.');
        $request->validate(['code' => ['required', 'string', 'max:10']]);
        $secret = $request->session()->get('2fa.secret');
        $step = $secret ? Totp::verify($secret, preg_replace('/\s+/', '', $request->input('code'))) : null;
        if ($step === null) {
            return back()->withErrors(['code' => __('Der Code passt nicht. Uhrzeit am Handy prüfen und den aktuellen Code eingeben.')]);
        }

        $user = $request->user();
        $user->forceFill([
            'two_factor_secret'       => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_last_step'    => $step,
        ])->save();
        $request->session()->forget('2fa.secret');
        $request->session()->put('mfa_pending', false);
        activity('Benutzer')->causedBy($user)->log('Zwei-Faktor eingerichtet');

        return redirect()->route('account')
            ->with('2fa.codes', $user->newRecoveryCodes())
            ->with('ok', __('Zwei-Faktor-Anmeldung ist eingeschaltet.'));
    }

    public function recoveryCodes(Request $request)
    {
        $user = $request->user();
        if (! $this->passwordOk($request, $user)) {
            return back()->withErrors(['password' => __('Das Passwort stimmt nicht.')]);
        }

        return redirect()->route('account')->with('2fa.codes', $user->newRecoveryCodes());
    }

    public function disableTwoFactor(Request $request)
    {
        $user = $request->user();
        if ($this->policy($request) === Mfa::REQUIRED) {
            return back()->withErrors(['password' => __('Für Ihre Gruppe ist die Zwei-Faktor-Anmeldung vorgeschrieben.')]);
        }
        if (! $this->passwordOk($request, $user)) {
            return back()->withErrors(['password' => __('Das Passwort stimmt nicht.')]);
        }
        $user->disableTwoFactor();
        activity('Benutzer')->causedBy($user)->log('Zwei-Faktor ausgeschaltet');

        return redirect()->route('account')->with('ok', __('Zwei-Faktor-Anmeldung ist ausgeschaltet.'));
    }

    /**
     * Bestätigung sicherheitsrelevanter Schritte: eigene Konten mit Passwort,
     * Verzeichniskonten mit einem aktuellen Code der Authenticator-App.
     *
     * Confirmation of security-relevant steps: own accounts with their password,
     * directory accounts with a current code from the authenticator app.
     */
    private function passwordOk(Request $request, User $user): bool
    {
        // Bremse wie beim Code der Anmeldung: 5 Fehlversuche je Konto, dann 5 Minuten Pause
        // Throttle like the sign-in code: 5 failed attempts per account, then a 5-minute pause
        $key = 'account-confirm:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['password' => __('Zu viele Fehlversuche. Bitte in :minuten Minuten erneut versuchen.', [
                'minuten' => (int) ceil(RateLimiter::availableIn($key) / 60),
            ])]);
        }

        $input = (string) $request->input('password', '');
        if ($user->isLocal()) {
            $ok = $user->password && Hash::check($input, $user->password);
        } else {
            // Ein Code der Authenticator-App gilt nur einmal (last_step wie bei der Anmeldung)
            // A code from the authenticator app is valid only once (last_step as at sign-in)
            $step = $user->hasTwoFactor()
                ? Totp::verify($user->two_factor_secret, (string) preg_replace('/\s+/', '', $input), $user->two_factor_last_step)
                : null;
            $ok = $step !== null;
            if ($ok) {
                $user->forceFill(['two_factor_last_step' => $step])->save();
            }
        }

        $ok ? RateLimiter::clear($key) : RateLimiter::hit($key, 300);

        return $ok;
    }
}
