<?php

return [
    'version' => '202605120008',
    'name' => 'Add December 24/31 deduction option to leave policy',
    'statements' => [
        <<<'SQL'
ALTER TABLE `leave_policy`
ADD COLUMN `dec_24_31_deduction` ENUM('full','half') NOT NULL DEFAULT 'full' AFTER `min_statutory_days`
SQL,
        <<<'SQL'
UPDATE `leave_policy`
SET `dec_24_31_deduction` = 'full'
WHERE `dec_24_31_deduction` IS NULL
SQL,
    ],
];
