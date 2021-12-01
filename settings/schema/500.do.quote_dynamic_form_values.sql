-- Add new column is_default in  quote_dynamic_form_values table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_dynamic_form_values'
        AND table_schema = DATABASE()
        AND column_name IN('is_default')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_dynamic_form_values ADD is_default TINYINT(1) NOT NULL DEFAULT '0';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add default value to quote_dynamic_form_values table

INSERT INTO quote_dynamic_form_values (label, label_slug, attribute_id, placeholder, is_required, store_id, is_default)
SELECT 'Customer name', 'customername', '1', 'Customer name', '1', '1', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, label_slug FROM quote_dynamic_form_values 
where store_id='1' and label_slug='customername');

-- Add default value to quote_dynamic_form_values table

INSERT INTO quote_dynamic_form_values (label, label_slug, attribute_id, placeholder, is_required, store_id, is_default)
SELECT 'Customer email', 'customeremail', '2', 'Customer email', '1', '1', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, label_slug FROM quote_dynamic_form_values 
where store_id='1' and label_slug='customeremail');