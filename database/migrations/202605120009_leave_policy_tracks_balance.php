<?php

return [
    'version' => '202605120009',
    'name' => 'Add tracks_balance column to leave_policy',
    'statements' => [
        <<<'SQL'
ALTER TABLE `leave_policy`
ADD COLUMN `tracks_balance` TINYINT(1) NOT NULL DEFAULT 1 AFTER `dec_24_31_deduction`
SQL,
        <<<'SQL'
UPDATE `leave_policy`
SET `tracks_balance` = 0
WHERE `category_key` IN ('sick', 'unpaid', 'bereavement', 'jury_duty')
SQL,
    ],
];
