-- Add new column unicode_character in app_currency table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'app_currency'
        AND table_schema = DATABASE()
        AND column_name IN('unicode_character')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE app_currency ADD unicode_character VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `app_currency` SET `unicode_character` = '&#36' WHERE `app_currency`.`xe_id` = 1;
UPDATE `app_currency` SET `unicode_character` = '&#8377' WHERE `app_currency`.`xe_id` = 2;
UPDATE `app_currency` SET `unicode_character` = '&#75' WHERE `app_currency`.`xe_id` = 3;
UPDATE `app_currency` SET `unicode_character` = '&#36' WHERE `app_currency`.`xe_id` = 4;
UPDATE `app_currency` SET `unicode_character` = '&#165' WHERE `app_currency`.`xe_id` = 5;
UPDATE `app_currency` SET `unicode_character` = '&#36' WHERE `app_currency`.`xe_id` = 6;
UPDATE `app_currency` SET `unicode_character` = '&#78' WHERE `app_currency`.`xe_id` = 7;
UPDATE `app_currency` SET `unicode_character` = '&#107' WHERE `app_currency`.`xe_id` = 8;
UPDATE `app_currency` SET `unicode_character` = '&#8369' WHERE `app_currency`.`xe_id` = 9;
UPDATE `app_currency` SET `unicode_character` = '&#3647' WHERE `app_currency`.`xe_id` = 10;
UPDATE `app_currency` SET `unicode_character` = '&#1088' WHERE `app_currency`.`xe_id` = 11;
UPDATE `app_currency` SET `unicode_character` = '&#8362' WHERE `app_currency`.`xe_id` = 12;
UPDATE `app_currency` SET `unicode_character` = '&#163' WHERE `app_currency`.`xe_id` = 13;
UPDATE `app_currency` SET `unicode_character` = '&#107' WHERE `app_currency`.`xe_id` = 14;
UPDATE `app_currency` SET `unicode_character` = '&#8364' WHERE `app_currency`.`xe_id` = 15;
UPDATE `app_currency` SET `unicode_character` = '&#36' WHERE `app_currency`.`xe_id` = 16;
UPDATE `app_currency` SET `unicode_character` = '&#122' WHERE `app_currency`.`xe_id` = 17;
UPDATE `app_currency` SET `unicode_character` = '&#67' WHERE `app_currency`.`xe_id` = 18;
UPDATE `app_currency` SET `unicode_character` = '&#36' WHERE `app_currency`.`xe_id` = 19;
UPDATE `app_currency` SET `unicode_character` = '&#36' WHERE `app_currency`.`xe_id` = 20;
UPDATE `app_currency` SET `unicode_character` = '&#107' WHERE `app_currency`.`xe_id` = 21;
UPDATE `app_currency` SET `unicode_character` = '&#36' WHERE `app_currency`.`xe_id` = 22;
UPDATE `app_currency` SET `unicode_character` = '&#70' WHERE `app_currency`.`xe_id` = 23;
UPDATE `app_currency` SET `unicode_character` = '&#165' WHERE `app_currency`.`xe_id` = 24;