<?php

return [
    // Demo-Modus: keine AD-/LDAP-Verbindung nötig. Anmeldung mit den
    // lokalen Seed-Benutzern admin/admin und manager/manager.
    // Demo mode: no AD/LDAP connection needed. Sign in with the
    // local seed users admin/admin and manager/manager.
    'enabled' => (bool) env('DEMO_MODE', false),
];
