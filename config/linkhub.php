<?php

return [

    // Backup: Zielordner (im Compose als eigener Ordner ./backups gemappt)
    // und Mindestaufbewahrung in Tagen (harte Untergrenze, in den
    // Einstellungen nur nach oben anpassbar)
    // Backup: target folder (mapped in Compose as its own folder ./backups)
    // and minimum retention in days (hard minimum, can only be raised
    // in the settings)
    'backup_path' => env('BACKUP_PATH', '/backups'),
    'backup_min_retention_days' => 4,

    // Fallback-AD-Gruppen (Einstellungen in der DB haben Vorrang)
    // Fallback AD groups (settings in the DB take precedence)
    'ad_groups' => [
        'admins'   => env('AD_GROUP_ADMINS', 'LinkHub-Admins'),
        'managers' => env('AD_GROUP_MANAGERS', 'LinkHub-Manager'),
    ],
];
