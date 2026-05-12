<?php

return [
    'version' => '202605120006',
    'name' => 'Add leave policy table',
    'statements' => [
        <<<'SQL'
CREATE TABLE IF NOT EXISTS `leave_policy` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_key` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `legal_days` DECIMAL(5,1) NOT NULL DEFAULT 0,
  `is_statutory` TINYINT(1) NOT NULL DEFAULT 0,
  `min_statutory_days` DECIMAL(5,1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_key` (`category_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
INSERT INTO `leave_policy` (`category_key`, `name`, `legal_days`, `is_statutory`, `min_statutory_days`) VALUES
('vacation',    'Vacaciones (Art. 38 ET)',                    22,  1, 22),
('sick',        'Baja por enfermedad',                         0,  0,  0),
('personal',    'Permiso personal',                            0,  0,  0),
('unpaid',      'Permiso no remunerado',                       0,  0,  0),
('maternity',   'Maternidad (Art. 48 ET)',                   112,  1, 112),
('paternity',   'Paternidad (Art. 48.4 ET)',                 112,  1, 112),
('marriage',    'Matrimonio (Art. 37.3.a ET)',                15,  1, 15),
('bereavement', 'Fallecimiento familiar (Art. 37.3.b ET)',     2,  1,  2),
('moving',      'Mudanza (Art. 37.3.g ET)',                    1,  1,  1),
('jury_duty',   'Deber público (Art. 37.3.d ET)',              0,  1,  0),
('other',       'Otro',                                        0,  0,  0)
ON DUPLICATE KEY UPDATE `category_key` = `category_key`
SQL,
    ],
];
