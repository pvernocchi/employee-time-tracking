<?php

return [
    'version' => '202605120009',
    'name' => 'Allow multiple work schedule slots per day',
    'statements' => [
        <<<'SQL'
ALTER TABLE `user_work_schedules`
ADD COLUMN `slot_index` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `day_of_week`
SQL,
        <<<'SQL'
ALTER TABLE `user_work_schedules`
DROP INDEX `idx_user_day`
SQL,
        <<<'SQL'
ALTER TABLE `user_work_schedules`
ADD UNIQUE KEY `idx_user_day_slot` (`user_id`, `day_of_week`, `slot_index`)
SQL,
    ],
];
