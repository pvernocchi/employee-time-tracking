<?php
/**
 * Application Configuration
 * Copy this file to config.php and update with your settings.
 */

return [
    'app' => [
        'name' => 'Employee Time Tracker',
        'url' => 'http://localhost',
        'timezone' => 'America/New_York',
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
];
