<?php

namespace App\Models;

use App\Models\Concerns\TwoFactor;
use App\Support\Directory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Benutzerkonto. Zwei Herkünfte (auth_source):
 *  - local: eigenes Konto mit Passwort, Rechte pflegt der Admin
 *  - ldap:  entsteht bei der ersten Anmeldung über das Verzeichnis; die
 *           Rechte werden bei jeder Anmeldung aus den Gruppen abgeleitet
 *
 * User account. Two origins (auth_source):
 *  - local: own account with password, rights are maintained by the admin
 *  - ldap:  created on the first sign-in via the directory; the
 *           rights are derived from the groups on every sign-in
 */
class User extends Authenticatable
{
    use TwoFactor;

    public const LOCAL = 'local';
    public const DIRECTORY = 'ldap';

    protected $fillable = ['username', 'name', 'email', 'guid', 'domain', 'auth_source', 'password', 'is_admin', 'is_manager', 'active', 'fox', 'last_login_at', 'mfa_policy'];
    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];
    protected $casts = [
        'password'      => 'hashed',
        'is_admin'      => 'boolean',
        'is_manager'    => 'boolean',
        'active'        => 'boolean',
        'fox'           => 'boolean',
        'last_login_at' => 'datetime',
        'two_factor_secret'         => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at'   => 'datetime',
    ];

    /**
     * Pflegerecht: Applikationen, Kategorien, Bereiche und Abteilungen
     *
     * Edit right: applications, categories, areas and departments
     */
    public function canEdit(): bool
    {
        return $this->is_admin || $this->is_manager;
    }

    public function isLocal(): bool
    {
        return $this->auth_source !== self::DIRECTORY;
    }

    public function origin(): string
    {
        return $this->isLocal() ? __('eigenes Konto') : __('Verzeichnis');
    }

    /**
     * Fuchs-Helfer anzeigen? Zentral abschaltbar (Admin), sonst je Person
     *
     * Show the fox helper? Can be switched off centrally (admin), otherwise per person
     */
    public function showsFox(): bool
    {
        return Setting::get('fox_enabled', '1') === '1' && $this->fox !== false;
    }

    /**
     * Rechte aus den Verzeichnisgruppen übernehmen (nur Verzeichniskonten).
     * Die Gruppen stehen in den Einstellungen als DN oder Gruppenname.
     *
     * Take over rights from the directory groups (directory accounts only).
     * The groups are stored in the settings as a DN or group name.
     */
    public function permissionsFromGroups(array $groups): void
    {
        $admins   = Setting::get('ad_group_admins', config('linkhub.ad_groups.admins'));
        $managers = Setting::get('ad_group_managers', config('linkhub.ad_groups.managers'));

        $this->forceFill([
            'is_admin'   => Directory::isMemberOf($groups, $admins),
            'is_manager' => Directory::isMemberOf($groups, $managers),
        ])->save();
    }

    /** Zufälliges Startpasswort für eigene Konten / Random initial password for own accounts */
    public static function initialPassword(): string
    {
        return \Illuminate\Support\Str::password(12, symbols: false);
    }
}
