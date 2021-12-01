-- Alter price_advanced_price_settings table advanced_price_type column type

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME = 'price_advanced_price_settings'
        ) > 0,
        "ALTER TABLE price_advanced_price_settings CHANGE advanced_price_type advanced_price_type TEXT NULL;",
        "SELECT 1"
    )
);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

/* Add new column screen_cost in  price_tier_values table */

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'price_tier_values'
        AND table_schema = DATABASE()
        AND column_name IN('screen_cost')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `price_tier_values` ADD `screen_cost` DECIMAL(10,2) NULL DEFAULT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;