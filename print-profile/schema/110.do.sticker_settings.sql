/* Add new column sticker_settings in  print_profiles table */

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'print_profiles'
        AND table_schema = DATABASE()
        AND column_name IN('sticker_settings')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `print_profiles` ADD `sticker_settings` TEXT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;