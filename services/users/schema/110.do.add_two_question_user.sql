-- Add new column second_question_id in  admin_users table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'admin_users'
        AND table_schema = DATABASE()
        AND column_name IN('second_question_id')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `admin_users` ADD `second_question_id` INT(10) NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new column second_answer in  admin_users table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'admin_users'
        AND table_schema = DATABASE()
        AND column_name IN('second_answer')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `admin_users` ADD `second_answer` TEXT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Change 'question_id' column name to 'first_question_id' in  admin_users table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'admin_users'
        AND table_schema = DATABASE()
        AND column_name IN('first_question_id')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `admin_users` CHANGE `question_id` `first_question_id` INT(10) DEFAULT '0' NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Change 'answer' column name to 'first_answer' in  admin_users table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'admin_users'
        AND table_schema = DATABASE()
        AND column_name IN('first_answer')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `admin_users` CHANGE `answer` `first_answer` TEXT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
