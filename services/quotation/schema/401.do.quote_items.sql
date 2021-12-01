-- Add new column is_variable_decoration in quote_items table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_items'
        AND table_schema = DATABASE()
        AND column_name IN('is_variable_decoration')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_items ADD is_variable_decoration TINYINT(1) NULL COMMENT '0-not a vdp, 1- vdp';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new column is_custom_size in quote_items table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_items'
        AND table_schema = DATABASE()
        AND column_name IN('is_custom_size')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_items ADD is_custom_size TINYINT(1) NULL COMMENT '0-not custom size, 1- custom size';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new column custom_size_dimension in quote_items table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_items'
        AND table_schema = DATABASE()
        AND column_name IN('custom_size_dimension')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_items ADD custom_size_dimension VARCHAR(100) NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new column custom_size_dimension_unit in quote_items table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_items'
        AND table_schema = DATABASE()
        AND column_name IN('custom_size_dimension_unit')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_items ADD custom_size_dimension_unit VARCHAR(50) NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- Insert quote_pdf_download in production_template_abbriviations 

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{quote_pdf_download}', '1' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{quote_pdf_download}' AND module_id=1);




