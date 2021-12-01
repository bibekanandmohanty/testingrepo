-- Add new column decoration_type in  product_settings table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'product_settings'
        AND table_schema = DATABASE()
        AND column_name IN('decoration_type')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `product_settings` ADD `decoration_type` varchar(30) DEFAULT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- Add new column decoration_dimensions in  product_settings table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'product_settings'
        AND table_schema = DATABASE()
        AND column_name IN('decoration_dimensions')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `product_settings` ADD `decoration_dimensions` text DEFAULT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;



