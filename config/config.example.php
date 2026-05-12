<?php
/**
 * Application Configuration
 * Copy this file to config.php and update with your settings.
 * Configured for Spanish labor law compliance (Real Decreto-ley 8/2019)
 */

return [
    'app' => [
        'name' => 'Employee Time Tracker',
        'url' => 'http://localhost',
        'timezone' => 'Europe/Madrid',
        'default_locale' => 'es',
        'supported_locales' => ['es', 'en', 'ca', 'eu', 'gl'],
    ],

    'database' => [
        'host' => 'localhost',
        'name' => 'time_tracking',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'lifetime' => 3600, // 1 hour
        'name' => 'ett_session',
    ],

    'compliance' => [
        'daily_max_hours' => 9,
        'weekly_max_hours' => 40,
        'annual_overtime_limit' => 80,
        'min_rest_between_days_hours' => 12,
        'break_after_hours' => 6,
        'min_break_minutes' => 15,
        'record_retention_years' => 4,
        'default_vacation_days' => 22,
        'lock_after_hours' => 72,
    ],

    'data_protection' => [
        'controller' => 'Nombre de la Empresa, S.L.',
        'purpose' => 'Registro obligatorio de jornada laboral conforme al artículo 34.9 del Estatuto de los Trabajadores.',
        'legal_basis' => 'Cumplimiento de obligación legal (Art. 6.1.c RGPD) y relación contractual laboral (Art. 6.1.b RGPD).',
        'retention' => 'Los datos se conservarán durante 4 años conforme a la normativa laboral vigente.',
        'rights' => 'Puede ejercer sus derechos de acceso, rectificación, supresión, portabilidad, limitación y oposición dirigiéndose a la dirección de la empresa.',
        'dpo_contact' => 'dpd@empresa.com',
    ],

    // SMTP settings are managed from the admin panel (Admin → Settings → SMTP)
    // and stored in the database. The log_path below is optional; leave it empty
    // to use the default <app_root>/logs/smtp.log location.
    'smtp' => [
        'log_path' => '',
        // A random secret used to encrypt the SMTP password stored in the database.
        // Generate with: php -r "echo bin2hex(random_bytes(32));"
        // If left empty, the password is stored without encryption.
        'secret' => '',
    ],
];
