<?php

return [
    'version' => '202605120009',
    'name' => 'Allow multiple work schedule slots per day',
    'statements' => [
        <<<'SQL'
SET @slot_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'user_work_schedules'
      AND column_name = 'slot_index'
)
SQL,
        <<<'SQL'
SET @slot_index_sql := IF(
    @slot_index_exists = 0,
    'ALTER TABLE `user_work_schedules` ADD COLUMN `slot_index` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `day_of_week`',
    'SELECT 1'
)
SQL,
        <<<'SQL'
PREPARE slot_index_stmt FROM @slot_index_sql
SQL,
        <<<'SQL'
EXECUTE slot_index_stmt
SQL,
        <<<'SQL'
DEALLOCATE PREPARE slot_index_stmt
SQL,
        <<<'SQL'
SET @idx_user_day_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'user_work_schedules'
      AND index_name = 'idx_user_day'
)
SQL,
        <<<'SQL'
SET @drop_idx_sql := IF(
    @idx_user_day_exists > 0,
    'ALTER TABLE `user_work_schedules` DROP INDEX `idx_user_day`',
    'SELECT 1'
)
SQL,
        <<<'SQL'
PREPARE drop_idx_stmt FROM @drop_idx_sql
SQL,
        <<<'SQL'
EXECUTE drop_idx_stmt
SQL,
        <<<'SQL'
DEALLOCATE PREPARE drop_idx_stmt
SQL,
        <<<'SQL'
SET @idx_user_day_slot_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'user_work_schedules'
      AND index_name = 'idx_user_day_slot'
)
SQL,
        <<<'SQL'
SET @add_idx_sql := IF(
    @idx_user_day_slot_exists = 0,
    'ALTER TABLE `user_work_schedules` ADD UNIQUE KEY `idx_user_day_slot` (`user_id`, `day_of_week`, `slot_index`)',
    'SELECT 1'
)
SQL,
        <<<'SQL'
PREPARE add_idx_stmt FROM @add_idx_sql
SQL,
        <<<'SQL'
EXECUTE add_idx_stmt
SQL,
        <<<'SQL'
DEALLOCATE PREPARE add_idx_stmt
SQL,
    ],
];
