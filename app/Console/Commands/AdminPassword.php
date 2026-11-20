<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Notfall: eigenes Admin-Konto anlegen oder sein Passwort neu setzen —
 * z. B. wenn das Verzeichnis nicht erreichbar ist oder niemand mehr hineinkommt.
 * Das neue Passwort ist ein Startpasswort (bei der Anmeldung ändern), laufende
 * Sitzungen des Kontos enden. --disable-2fa setzt zusätzlich die
 * Zwei-Faktor-Anmeldung zurück (Handy verloren).
 *   docker exec linkhub php artisan linkhub:admin-password [name] [--disable-2fa]
 * (alter Name linkhub:admin-passwort bleibt als Alias)
 *
 * Emergency: create an own (local) admin account or reset its password —
 * e.g. when the directory is unreachable or nobody can get in any more.
 * The new password is an initial password (change it at sign-in), running
 * sessions of the account end. --disable-2fa also resets the two-factor
 * sign-in (phone lost).
 *   docker exec linkhub php artisan linkhub:admin-password [name] [--disable-2fa]
 * (the old name linkhub:admin-passwort remains as an alias)
 */
class AdminPassword extends Command
{
    protected $signature = 'linkhub:admin-password {username=admin}
        {--disable-2fa : Zwei-Faktor-Anmeldung dieses Kontos zurücksetzen / reset this account\'s two-factor sign-in}';

    /** Alter Name aus der Zeit vor Standard 10 / Old name from before standard 10 */
    protected $aliases = ['linkhub:admin-passwort'];

    protected $description = 'Legt ein eigenes Admin-Konto an bzw. setzt dessen Passwort neu (optional ohne Zwei-Faktor)';

    public function handle(): int
    {
        $name = mb_strtolower(trim((string) $this->argument('username')));
        $user = User::where('username', $name)->first();

        if ($user && ! $user->isLocal()) {
            $this->error("„{$name}“ ist ein Verzeichniskonto — sein Passwort verwaltet das Verzeichnis. Bitte einen anderen Namen wählen.");

            return self::FAILURE;
        }

        $new = User::initialPassword();
        if (! $user) {
            $user = User::create([
                'username' => $name, 'name' => 'Administrator', 'auth_source' => User::LOCAL,
                'password' => $new, 'must_change_password' => true,
                'is_admin' => true, 'is_manager' => true, 'active' => true,
            ]);
            $this->info("Eigenes Admin-Konto „{$name}“ angelegt.");
        } else {
            $user->setInitialPassword($new)->forceFill(['is_admin' => true, 'is_manager' => true, 'active' => true])->save();
            $this->info("Passwort für „{$name}“ neu gesetzt, Konto ist Administrator und aktiv. Laufende Sitzungen des Kontos sind beendet.");
        }
        RateLimiter::clear('sign-in:' . $name);

        $disabled = false;
        if ($this->option('disable-2fa') && $user->hasTwoFactor()) {
            $user->disableTwoFactor();
            $disabled = true;
        }
        activity('Benutzer')->performedOn($user)
            ->log($disabled ? 'Notzugang: Passwort und Zwei-Faktor zurückgesetzt' : 'Notzugang: Passwort neu gesetzt');

        $this->line("Startpasswort: {$new}");
        $this->line('Es muss bei der Anmeldung geändert werden; bis dahin ist nur „Mein Konto“ erreichbar.');
        if ($this->option('disable-2fa')) {
            $this->line($disabled
                ? 'Zwei-Faktor-Anmeldung zurückgesetzt; ist sie Pflicht, wird sie nach der Anmeldung neu eingerichtet.'
                : 'Zwei-Faktor-Anmeldung war nicht eingerichtet.');
        }

        return self::SUCCESS;
    }
}
