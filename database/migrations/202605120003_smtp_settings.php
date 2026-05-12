<?php

return [
    'version' => '202605120003',
    'name' => 'Add SMTP settings table',
    'statements' => [
        <<<'SQL'
CREATE TABLE IF NOT EXISTS `smtp_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
INSERT INTO `smtp_settings` (`setting_key`, `setting_value`) VALUES
('smtp_enabled', '0'),
('smtp_host', 'localhost'),
('smtp_port', '587'),
('smtp_encryption', 'tls'),
('smtp_auth', '0'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_from_email', ''),
('smtp_from_name', ''),
('smtp_log_enabled', '0')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`
SQL,
    ],
];
