/* Add new column extra_data in quote_item_files table */

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_item_files'
        AND table_schema = DATABASE()
        AND column_name IN('extra_data')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_item_files ADD extra_data TEXT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;