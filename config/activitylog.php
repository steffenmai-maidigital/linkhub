<?php

return [
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),
    'delete_records_older_than_days' => 365 * 10, // Bereinigung macht die Löschroutine / deletion routine cleans up
    'default_log_name' => 'default',
    'default_auth_driver' => null,
    'subject_returns_soft_deleted_models' => false,

    // Eigenes Modell mit Hash-Kette (revisionssicheres Audit)
    // Own model with hash chain (audit-proof log)
    'activity_model' => \App\Models\ActivityLog::class,
    'table_name' => 'activity_log',
    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),
];
