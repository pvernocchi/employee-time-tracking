<?php

return [
    'version' => '202605120002',
    'name' => 'Add manager hierarchy to users',
    'statements' => [
        <<<'SQL'
ALTER TABLE `users`
  ADD COLUMN `manager_id` INT UNSIGNED DEFAULT NULL AFTER `hourly_rate`,
  ADD KEY `idx_users_manager` (`manager_id`),
  ADD CONSTRAINT `fk_users_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL,
    ],
]
;
