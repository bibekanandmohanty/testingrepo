-- Add new column is_ready_to_send in quotations table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quotations'
        AND table_schema = DATABASE()
        AND column_name IN('is_ready_to_send')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quotations ADD is_ready_to_send TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-not ready, 1-ready';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


ALTER TABLE `quotations` CHANGE `customer_id` `customer_id` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
ALTER TABLE `quotations` CHANGE `shipping_id` `shipping_id` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
ALTER TABLE `quotations` CHANGE `created_by` `created_by` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT 'admin, customer, agent';
ALTER TABLE `quotations` CHANGE `quote_source` `quote_source` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT 'admin, tool, form';
ALTER TABLE `quotations` CHANGE `title` `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
ALTER TABLE `quotations` CHANGE `ship_by_date` `ship_by_date` DATETIME NULL;
ALTER TABLE `quotations` CHANGE `exp_delivery_date` `exp_delivery_date` DATETIME NULL;
ALTER TABLE `quotations` CHANGE `is_artwork` `is_artwork` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `quotations` CHANGE `is_rush` `is_rush` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `quotations` CHANGE `rush_type` `rush_type` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'percentage, flat';
ALTER TABLE `quotations` CHANGE `draft_flag` `draft_flag` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0-send to customer,1-save as draft';
ALTER TABLE `quotations` CHANGE `created_by_id` `created_by_id` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;


ALTER TABLE `quote_items` CHANGE `artwork_type` `artwork_type` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT 'uploaded_file, design_tool, no_decoration';
ALTER TABLE `quote_items` CHANGE `quote_id` `quote_id` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
ALTER TABLE `quote_items` CHANGE `product_id` `product_id` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
ALTER TABLE `quote_items` CHANGE `quantity` `quantity` INT(11) NULL;
ALTER TABLE `quote_items` CHANGE `design_cost` `design_cost` FLOAT(10,2) NULL;
ALTER TABLE `quote_items` CHANGE `unit_total` `unit_total` FLOAT(10,2) NULL; 

ALTER TABLE `quote_log` CHANGE `user_id` `user_id` INT(11) NULL COMMENT 'i.e agent data in case of agent assignment';


-- Add new column quotation_request_id in quotations table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quotations'
        AND table_schema = DATABASE()
        AND column_name IN('quotation_request_id')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quotations ADD quotation_request_id INT(11) NULL"
));
PREPARE stmt FROM @s; 
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new column is_decorated_product in quote_items table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_items'
        AND table_schema = DATABASE()
        AND column_name IN('is_decorated_product')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_items ADD is_decorated_product TINYINT(1) NULL DEFAULT '0' COMMENT '0- not decorated product, 1- decorated product';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new column is_redesign in quote_items table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_items'
        AND table_schema = DATABASE()
        AND column_name IN('is_redesign')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_items ADD is_redesign TINYINT(1) NULL DEFAULT '1' COMMENT '0- no redesign option, 1- redesign option';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new column form_type in quotation_request_form_values table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quotation_request_form_values'
        AND table_schema = DATABASE()
        AND column_name IN('form_type')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quotation_request_form_values ADD form_type VARCHAR(255) NULL"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;



