-- Add new column customer_name in quotations table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quotations'
        AND table_schema = DATABASE()
        AND column_name IN('customer_name')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quotations ADD customer_name varchar(255) NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- Add new column customer_email in quotations table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quotations'
        AND table_schema = DATABASE()
        AND column_name IN('customer_email')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quotations ADD customer_email varchar(255) NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- Add new column customer_availability in quotations table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quotations'
        AND table_schema = DATABASE()
        AND column_name IN('customer_availability')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quotations ADD customer_availability TINYINT(1) NOT NULL DEFAULT '0';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- Add new column attribute in quote_item_variants table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_item_variants'
        AND table_schema = DATABASE()
        AND column_name IN('attribute')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_item_variants ADD attribute TEXT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


