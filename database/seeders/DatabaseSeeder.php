<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SettingsSeeder::class);

        if (config('demo.enabled')) {
            $this->call(DemoSeeder::class);

            return;
        }

        // Erster Administrator als eigenes Konto — nur, wenn noch kein Konto
        // existiert. Ohne ADMIN_PASSWORD ein zufälliges Startpasswort, das
        // einmalig im Container-Protokoll steht (kein bekanntes „admin/admin“).
        // First administrator as an own (local) account — only if no account
        // exists yet. Without ADMIN_PASSWORD a random initial password, shown
        // once in the container log (no well-known "admin/admin").
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
            'is_admin'    => true,
            'is_manager'  => true,
            'active'      => true,
        ]);

        $this->command?->info("Administrator „{$name}“ als eigenes Konto angelegt.");
        if ($random) {
            $this->command?->warn('==============================================================');
            $this->command?->warn("  Erstes Admin-Passwort für „{$name}“: {$password}");
            $this->command?->warn('  Bitte gleich anmelden und unter Benutzer ein eigenes vergeben.');
            $this->command?->warn('  Es wird nicht erneut angezeigt.');
            $this->command?->warn('==============================================================');
        }
    }
}
