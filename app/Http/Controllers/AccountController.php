<?php

namespace App\Http\Controllers;

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

/**
 * Eigenes Konto: Passwort (eigene Konten), Zwei-Faktor-Anmeldung, Fuchs-Helfer
 *
 * Own account: password (own accounts), two-factor sign-in, fox helper
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
        ]);
    }

    private function policy(Request $request): string
    {
        $s = $request->session()->get('mfa_policy');

        return isset(Mfa::LEVELS[$s]) ? $s : Mfa::policy($request->user());
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
        $user->forceFill(['password' => $data['password']])->save();
        activity('Benutzer')->causedBy($user)->log('Eigenes Passwort geändert');

        return redirect()->route('account')->with('ok', __('Passwort geändert.'));
    }

    public function startTwoFactor(Request $request)
    {
        abort_unless($this->policy($request) !== Mfa::OFF, 403, 'Zwei-Faktor ist für diese Gruppe ausgeschaltet.');
        $request->session()->put('2fa.secret', Totp::newSecret());

        return redirect()->route('account');
    }

    public function confirmTwoFactor(Request $request)
    {
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

    /** Fuchs-Helfer für sich selbst ein- oder ausschalten / Switch the fox helper on or off for oneself */
    public function fox(Request $request)
    {
        $on = $request->boolean('fox');
        $request->user()->forceFill(['fox' => $on])->save();

        return back()->with('ok', $on ? __('Der Fuchs ist wieder da.') : __('Der Fuchs macht Pause.'));
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
        $input = (string) $request->input('password', '');
        if ($user->isLocal()) {
            return $user->password && Hash::check($input, $user->password);
        }

        return $user->hasTwoFactor() && Totp::verify($user->two_factor_secret, preg_replace('/\s+/', '', $input)) !== null;
    }
}
