-- Add new column is_custom_size in  product_settings table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'product_settings'
        AND table_schema = DATABASE()
        AND column_name IN('is_custom_size')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `product_settings` ADD `is_custom_size` TINYINT(1) DEFAULT '0';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;