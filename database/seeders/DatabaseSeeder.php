<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SettingsSeeder::class);

        if (config('demo.enabled')) {
            $this->call(DemoSeeder::class);

            return;
        }

        // Lief die Datenbank vorher im Demo-Modus, haben admin und manager noch
        // ihre bekannten Demo-Passwörter: die werden jetzt ersetzt
        // If the database ran in demo mode before, admin and manager still have
        // their well-known demo passwords: they are replaced now
        $this->secureDemoAccounts();

        // Erster Administrator als eigenes Konto — nur, wenn noch kein Konto
        // existiert. Ohne ADMIN_PASSWORD ein zufälliges Startpasswort, das
        // einmalig im Container-Protokoll steht (kein bekanntes „admin/admin“)
        // und bei der ersten Anmeldung geändert werden muss.
        // First administrator as an own (local) account — only if no account
        // exists yet. Without ADMIN_PASSWORD a random initial password, shown
        // once in the container log (no well-known "admin/admin") and to be
        // changed at the first sign-in.
        if (User::exists()) {
            return;
        }
        $password = (string) env('ADMIN_PASSWORD', '');
        $random = $password === '';
        if ($random) {
            $password = User::initialPassword();
        }
        $name = mb_strtolower((string) env('ADMIN_USERNAME', 'admin')) ?: 'admin';

        User::create([
            'username'    => $name,
            'name'        => 'Administrator',
            'auth_source' => User::LOCAL,
            'password'    => $password,
            'must_change_password' => $random,
            'is_admin'    => true,
            'is_manager'  => true,
            'active'      => true,
        ]);

        $this->command?->info("Administrator „{$name}“ als eigenes Konto angelegt.");
        if ($random) {
            $this->command?->warn('==============================================================');
            $this->command?->warn("  Erstes Admin-Passwort für „{$name}“: {$password}");
            $this->command?->warn('  Bei der ersten Anmeldung muss es unter „Mein Konto“ geändert werden.');
            $this->command?->warn('  Es wird nicht erneut angezeigt.');
            $this->command?->warn('==============================================================');
        }
    }

    /**
     * Demo-Konten nach dem Wechsel in den Normalbetrieb entschärfen: Jedes
     * eigene Konto der Demo (admin/admin, manager/manager), dessen Passwort
     * noch das Demo-Passwort ist, bekommt ein neues Startpasswort; es steht
     * einmal im Container-Protokoll. Für den Administrator gilt ADMIN_PASSWORD,
     * wenn gesetzt. Konten ohne Admin-Rechte werden zusätzlich gesperrt.
     *
     * Defuse demo accounts after switching to normal operation: every own
     * demo account (admin/admin, manager/manager) whose password is still the
     * demo password gets a new initial password, shown once in the container
     * log. The administrator gets ADMIN_PASSWORD if set. Accounts without
     * admin rights are locked as well.
     */
    private function secureDemoAccounts(): void
    {
        foreach (['admin' => 'admin', 'manager' => 'manager'] as $username => $demoPassword) {
            $user = User::where('username', $username)->where('auth_source', User::LOCAL)->first();
            if (! $user || ! $user->password || ! Hash::check($demoPassword, $user->password)) {
                continue;
            }
            $given = (string) env('ADMIN_PASSWORD', '');
            $useGiven = $user->is_admin && $given !== '' && $given !== $demoPassword
                && $username === (mb_strtolower((string) env('ADMIN_USERNAME', 'admin')) ?: 'admin');
            $password = $useGiven ? $given : User::initialPassword();
            // Ein neues Startpasswort muss bei der Anmeldung geändert werden, ADMIN_PASSWORD nicht
            // A new initial password must be changed at sign-in, ADMIN_PASSWORD need not be
            $user->forceFill(['password' => $password, 'must_change_password' => ! $useGiven]
                + ($user->is_admin ? [] : ['active' => false]))->save();
            activity('Benutzer')->performedOn($user)->log($user->is_admin
                ? 'Demo-Passwort ersetzt (Normalbetrieb)'
                : 'Demo-Passwort ersetzt und Konto gesperrt (Normalbetrieb)');

            $this->command?->warn('==============================================================');
            $this->command?->warn("  Demo-Konto „{$username}“: Das Demo-Passwort gilt nicht mehr.");
            if ($useGiven) {
                $this->command?->warn('  Neues Passwort: der Wert aus ADMIN_PASSWORD.');
            } else {
                $this->command?->warn("  Neues Startpasswort für „{$username}“: {$password}");
                $this->command?->warn('  Es muss bei der Anmeldung unter „Mein Konto“ geändert werden.');
            }
            if (! $user->is_admin) {
                $this->command?->warn('  Das Konto ist gesperrt; ein Administrator kann es unter Benutzer freigeben.');
            }
            $this->command?->warn('==============================================================');
        }
    }
}
