/* Add new column uv_file in decoration_objects table */

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'orders'
        AND table_schema = DATABASE()
        AND column_name IN('order_status')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `orders` ADD COLUMN `order_status` VARCHAR(50) NOT NULL DEFAULT 'received';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;