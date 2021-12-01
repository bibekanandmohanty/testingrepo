-- Add new column order_number in orders table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'orders'
        AND table_schema = DATABASE()
        AND column_name IN('order_number')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE orders ADD order_number VARCHAR(50) NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

