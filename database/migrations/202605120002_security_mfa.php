<?php

return [
    'version' => '202605120002',
    'name' => 'Security settings and MFA tables',
    'statements' => [
        <<<'SQL'
CREATE TABLE IF NOT EXISTS `security_settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS `user_mfa` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('totp','webauthn') NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `credential_id` VARCHAR(1024) DEFAULT NULL,
  `credential_data` TEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_mfa` (`user_id`, `type`),
  CONSTRAINT `fk_mfa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
INSERT INTO `security_settings` (`setting_key`, `setting_value`) VALUES
('mfa_policy', 'optional'),
('captcha_provider', 'none'),
('captcha_site_key', ''),
('captcha_secret_key', ''),
('captcha_recaptcha_version', 'v2')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`
SQL,
        <<<'SQL'
ALTER TABLE `users`
  ADD COLUMN `mfa_required` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`
SQL,
    ],
];
