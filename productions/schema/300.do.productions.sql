-- Add new column production_status in  orders table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'orders'
        AND table_schema = DATABASE()
        AND column_name IN('production_status')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE orders ADD production_status ENUM('0','1','2') NOT NULL DEFAULT '0' COMMENT '0-Not started, 1-In-Progress, 2-Completed';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new column production_percentage in  orders table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'orders'
        AND table_schema = DATABASE()
        AND column_name IN('production_percentage')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE orders ADD production_percentage INT(4) NOT NULL DEFAULT 0;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new column store_id in  orders table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'orders'
        AND table_schema = DATABASE()
        AND column_name IN('store_id')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE orders ADD store_id INT(4) NOT NULL DEFAULT 1;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new column customer_id in  orders table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'orders'
        AND table_schema = DATABASE()
        AND column_name IN('customer_id')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE orders ADD customer_id VARCHAR(50) NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexing in orders table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE table_name = 'orders'
        AND table_schema = DATABASE()
        AND index_name =  'orders_index_key_01'
    ) <= 0,
    "ALTER TABLE orders ADD INDEX orders_index_key_01 (order_id, customer_id);",
  "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Data for the table `production_hub_settings` for Production 
INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '4', 'is_production', 'true', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings 
where store_id='1' and module_id='4' and setting_key='is_production');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '4', 'job_card', '{"prefix":"IM","starting_number":1,"postfix":"NXT"}', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings 
where store_id='1' and module_id='4' and setting_key='job_card');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '4', 'is_communication_enabled', 'true', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings 
where store_id='1' and module_id='4' and setting_key='is_communication_enabled');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '4', 'is_automatic_job_creation', 'false', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings 
where store_id='1' and module_id='4' and setting_key='is_automatic_job_creation');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '4', 'mandatory_purchase_order', 'false', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='4' and setting_key='mandatory_purchase_order');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '4', 'mark_as_done', 'true', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='4' and setting_key='mark_as_done');

-- Data for the table `production_status` for Production

INSERT INTO production_status (store_id, status_name, color_code, module_id, is_default, sort_order, status)
SELECT '1', 'Start Printing', '#49ce49', '4', '1', '1', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, status_name FROM production_status  
where store_id='1' and module_id='4' and status_name='Start Printing');

INSERT INTO production_status (store_id, status_name, color_code, module_id, is_default, sort_order, status)
SELECT '1', 'Shipping', '#8c23e6', '4', '1', '2', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, status_name FROM production_status  
where store_id='1' and module_id='4' and status_name='Shipping');


-- Table structure for table `production_status_print_profile_rel` 

CREATE TABLE IF NOT EXISTS `production_status_print_profile_rel` (
  `status_id` int(11) DEFAULT NULL,
  `print_profile_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO production_status_print_profile_rel (status_id, print_profile_id)
SELECT (SELECT xe_id FROM production_status WHERE status_name = 'Start Printing' AND module_id = 4 AND store_id = 1), '1'
FROM DUAL
WHERE NOT EXISTS (SELECT status_id, print_profile_id FROM production_status_print_profile_rel where status_id= (SELECT xe_id FROM production_status WHERE status_name = 'Start Printing' AND module_id = 4 AND store_id = 1) and print_profile_id='1');

INSERT INTO production_status_print_profile_rel (status_id, print_profile_id)
SELECT (SELECT xe_id FROM production_status WHERE status_name = 'Start Printing' AND module_id = 4 AND store_id = 1), '2'
FROM DUAL
WHERE NOT EXISTS (SELECT status_id, print_profile_id FROM production_status_print_profile_rel where status_id= (SELECT xe_id FROM production_status WHERE status_name = 'Start Printing' AND module_id = 4 AND store_id = 1) and print_profile_id='2');

-- Table structure for table `production_status_features` 

CREATE TABLE IF NOT EXISTS `production_status_features` (
  `status_id` int(11) DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `is_global` tinyint(1) NOT NULL DEFAULT '0',
  `is_group` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO production_status_features (status_id, duration, is_global, is_group)
SELECT (SELECT xe_id FROM production_status WHERE status_name = 'Start Printing' AND module_id = 4 AND store_id = 1), '12', '0', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT status_id FROM production_status_features where status_id= (SELECT xe_id FROM production_status WHERE status_name = 'Start Printing' AND module_id = 4 AND store_id = 1));

INSERT INTO production_status_features (status_id, duration, is_global, is_group)
SELECT (SELECT xe_id FROM production_status WHERE status_name = 'Shipping' AND module_id = 4 AND store_id = 1), '12', '1', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT status_id FROM production_status_features where status_id= (SELECT xe_id FROM production_status WHERE status_name = 'Shipping' AND module_id = 4 AND store_id = 1));


-- Table structure for table `production_status_assignee_rel` 

CREATE TABLE IF NOT EXISTS `production_status_assignee_rel` (
  `status_id` int(11) NOT NULL,
  `assignee_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Data for the table `production_email_templates` 
INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1', '4', 'start_printing', 'Updates on your order id #{order_id}', '<span>Hi {customer_name},<br><br>We are happy to inform you that the printing of the item {item_name} for order id# {order_id} is completed.You will be notified further on your order related updates.<br><br>Thank you.</span><br>', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates  
where store_id='1' and module_id='4' and template_type_name='start_printing');

INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1', '4', 'shipping', 'Updates on your order id #{order_id}', '<span>Hi {customer_name},<br><br>We are happy to inform you that the shipping of the item {item_name} for order id# {order_id} is completed.You will be notified with the shipping details very soon.<br><br>Thank you.</span><br>', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates  
where store_id='1' and module_id='4' and template_type_name='shipping');

-- Data for the table `production_template_abbriviations` for Production 

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{job_id}','4'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations  
where abbr_name='{job_id}' and module_id='4');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{order_id}','4'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations  
where abbr_name='{order_id}' and module_id='4');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{order_item_id}','4'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations  
where abbr_name='{order_item_id}' and module_id='4');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{customer_name}','4'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations  
where abbr_name='{customer_name}' and module_id='4');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{customer_email}','4'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations  
where abbr_name='{customer_email}' and module_id='4');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{item_name}','4'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations  
where abbr_name='{item_name}' and module_id='4');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{print_profile}','4'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations  
where abbr_name='{print_profile}' and module_id='4');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{stage_name}','4'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations  
where abbr_name='{stage_name}' and module_id='4');

-- Table structure for table production_jobs 

CREATE TABLE IF NOT EXISTS `production_jobs` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` INT(4) NOT NULL,
  `job_id` VARCHAR(30) NOT NULL,
  `order_id` VARCHAR(50) NOT NULL,
  `order_item_id` VARCHAR(50) NOT NULL,
  `order_item_quantity` INT(11) NOT NULL,
  `job_title` VARCHAR(255) NOT NULL,
  `job_status` ENUM('not-started','progressing','completed','delay') NOT NULL DEFAULT 'not-started',
  `note` TEXT  NULL,
  `comp_percentage` INT(4) NOT NULL DEFAULT 0,
  `due_date` DATETIME  NULL,
  `scheduled_date` DATETIME  NULL,
  `created_at` DATETIME NOT NULL,
  `current_stage_id` INT(11) NOT NULL,
  PRIMARY KEY (xe_id),
  KEY production_jobs_index_key_01 (job_id,order_id,job_title,store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table production_job_notes 

CREATE TABLE IF NOT EXISTS `production_job_notes` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `note` TEXT NOT NULL,
  `user_type` ENUM('admin','agent') NOT NULL , 
  `user_id` INT(11) NOT NULL , 
  `seen_flag` ENUM('0','1') NOT NULL DEFAULT '0',
  `created_date` DATETIME NOT NULL,
  PRIMARY KEY (xe_id),
  KEY production_job_notes_index_key_01 (job_id,created_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table `quote_internal_note_files` 

CREATE TABLE IF NOT EXISTS `production_job_note_files` ( 
  `xe_id` INT(11) NOT NULL AUTO_INCREMENT , 
  `note_id` INT(11) NOT NULL ,
  `file` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table production_job_stages 

CREATE TABLE IF NOT EXISTS `production_job_stages` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` INT(11) NOT NULL,
  `print_method_id` INT(11) NOT NULL,
  `stages_id` INT(11) NOT NULL,
  `stage_name` VARCHAR(255) NOT NULL,
  `stage_color_code` VARCHAR(255) NOT NULL,
  `created_date` DATETIME NOT NULL,
  `starting_date` DATETIME  NULL,
  `exp_completion_date` DATETIME  NULL,
  `completion_date` DATETIME NULL,
  `status` ENUM('not-started','in-progress','completed','delay') NOT NULL DEFAULT 'in-progress',
  `message` TEXT NULL,
  PRIMARY KEY (xe_id),
  KEY production_job_stages_index_key_01 (job_id,print_method_id,stages_id,exp_completion_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table production_job_agents 

CREATE TABLE IF NOT EXISTS `production_job_agents` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` INT(11) NOT NULL,
  `job_stage_id` INT(11) NOT NULL,
  `is_group` TINYINT(1) NOT NULL,
  `agent_id` INT(11) NOT NULL,
  PRIMARY KEY (xe_id),
  KEY production_job_agents_index_key_01 (job_id,job_stage_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table production_job_log 

CREATE TABLE IF NOT EXISTS `production_job_log` (
  `xe_id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_id` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `user_type` ENUM('admin','agent','customer') NOT NULL,
  `user_id` INT(11) NOT NULL,
  `created_date` DATETIME NOT NULL,
  PRIMARY KEY (xe_id),
  KEY production_job_log_index_key_01 (job_id,created_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



