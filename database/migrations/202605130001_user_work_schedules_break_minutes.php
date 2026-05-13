<?php

return [
    'version' => '202605130001',
    'name' => 'Add editable break minutes to user work schedules',
    'statements' => [
        <<<'SQL'
SET @break_minutes_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'user_work_schedules'
      AND column_name = 'break_minutes'
)
SQL,
        <<<'SQL'
SET @break_minutes_sql := IF(
    @break_minutes_exists = 0,
    'ALTER TABLE `user_work_schedules` ADD COLUMN `break_minutes` INT UNSIGNED NULL DEFAULT NULL AFTER `is_working`',
    'SELECT 1'
)
SQL,
        <<<'SQL'
PREPARE break_minutes_stmt FROM @break_minutes_sql
SQL,
        <<<'SQL'
EXECUTE break_minutes_stmt
SQL,
        <<<'SQL'
DEALLOCATE PREPARE break_minutes_stmt
SQL,
    ],
];
