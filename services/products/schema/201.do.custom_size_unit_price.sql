-- Add new column custom_size_unit_price in  product_settings table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'product_settings'
        AND table_schema = DATABASE()
        AND column_name IN('custom_size_unit_price')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `product_settings` ADD `custom_size_unit_price` decimal(6,2) DEFAULT '0.00';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;