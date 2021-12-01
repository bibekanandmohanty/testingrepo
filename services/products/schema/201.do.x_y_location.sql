-- Add new column locations in  product_decoration_settings table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'product_decoration_settings'
        AND table_schema = DATABASE()
        AND column_name IN('locations')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `product_decoration_settings` ADD `locations` text DEFAULT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;




