<?php

return [
    'version' => '202605120004',
    'name' => 'Add user preferences table',
    'statements' => [
        <<<'SQL'
CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `timezone` VARCHAR(100) NOT NULL DEFAULT 'Europe/Madrid',
  `locale` VARCHAR(5) NOT NULL DEFAULT 'es',
  `theme` ENUM('light','dark') NOT NULL DEFAULT 'light',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_prefs` (`user_id`),
  CONSTRAINT `fk_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
    ],
];
