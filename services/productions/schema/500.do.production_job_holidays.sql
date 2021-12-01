-- Adding default data for table `production_hub_settings` 

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '4', 'time_format', '24', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='4' and setting_key='time_format');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '4', 'working_hours', '{"starts_at":"10:30","ends_at":"19:30"}', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='4' and setting_key='working_hours');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '4', 'weekends', '["sat"]', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='4' and setting_key='weekends');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '4', 'is_barcode_enable', 'false', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='4' and setting_key='is_barcode_enable');


-- Table structure for table `production_job_holidays` 

CREATE TABLE IF NOT EXISTS `production_job_holidays` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(4) NOT NULL,
  `holiday_name` varchar(255) NULL,
  `day` varchar(50) NULL,
  `date` date NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Add indexing in quotations table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE table_name = 'quotations'
        AND table_schema = DATABASE()
        AND index_name =  'quotation_index_key_01'
    ) <= 0,
    "ALTER TABLE quotations ADD INDEX quotation_index_key_01 (quote_id, customer_id, customer_name, customer_email, title, status_id);",
  "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- Add indexing in quote_items table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE table_name = 'quote_items'
        AND table_schema = DATABASE()
        AND index_name =  'quote_items_index_key_01'
    ) <= 0,
     "ALTER TABLE quote_items ADD INDEX quote_items_index_key_01 (quote_id, product_id);",
  "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;



-- Add indexing in purchase_order table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE table_name = 'purchase_order'
        AND table_schema = DATABASE()
        AND index_name =  'purchase_order_index_key_01'
    ) <= 0,
    "ALTER TABLE purchase_order ADD INDEX purchase_order_index_key_01 (po_id, status_id);",
  "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexing in vendor table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE table_name = 'vendor'
        AND table_schema = DATABASE()
        AND index_name =  'vendor_index_key_01'
    ) <= 0,
    "ALTER TABLE vendor ADD INDEX vendor_index_key_01 (company_name, contact_name, email, country_code, state_code, city, zip_code);",
  "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;