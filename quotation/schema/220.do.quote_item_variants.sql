SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_item_variants'
        AND table_schema = DATABASE()
        AND column_name IN('options')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_item_variants ADD options TEXT NULL DEFAULT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;