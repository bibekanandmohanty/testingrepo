/*Table structure for table purchase_order*/

CREATE TABLE IF NOT EXISTS purchase_order (
  xe_id int(11)  NOT NULL AUTO_INCREMENT,
  po_id varchar(20) NOT NULL,
  status_id int(4) NOT NULL,
  store_id int(4) NOT NULL,
  vendor_id int(7) NOT NULL,
  ship_address_id int(7) NOT NULL,
  po_notes text DEFAULT NULL,
  expected_delivery_date date NOT NULL,
  created_at date NOT NULL,
  PRIMARY KEY(xe_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table purchase_order_details*/

CREATE TABLE IF NOT EXISTS purchase_order_items (
  xe_id int(11) NOT NULL AUTO_INCREMENT,
  purchase_order_id int(11) NOT NULL,
  order_id varchar(60) NOT NULL,
  order_item_id varchar(60) NOT NULL,
  status_id int(4) NOT NULL,
  PRIMARY KEY(xe_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table po_line_item_status*/

CREATE TABLE IF NOT EXISTS po_line_item_status (
  xe_id int(7) NOT NULL AUTO_INCREMENT,
  store_id int(4) NOT NULL,
  status_name varchar(20) NOT NULL,
  color_code varchar(10) NOT NULL,
  is_default enum('1','0') NOT NULL DEFAULT '1',
  sort_order int(4) NOT NULL,
  status enum('1','0') NOT NULL DEFAULT '1',
  PRIMARY KEY(xe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/*Table structure for table `purchase_order_status`*/

CREATE TABLE `purchase_order_status` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(4) NOT NULL,
  `status_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `color_code` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `module_id` int(11) NOT NULL,
  `is_default` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
  `sort_order` int(4) NOT NULL,
  `status` enum('1','0') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COLLATE=utf8_unicode_ci;

-- Data for the table `purchase_order_status`

INSERT INTO purchase_order_status (xe_id, store_id, status_name, color_code, module_id, is_default, sort_order, status)
SELECT '6', '1', 'PO sent', '#1a73e8', '3', '1', '1', '1'
FROM DUAL WHERE NOT EXISTS (SELECT xe_id, store_id, module_id, status_name FROM purchase_order_status  
WHERE xe_id='6' and store_id='1' and module_id='3' and status_name='PO sent');

INSERT INTO purchase_order_status (xe_id, store_id, status_name, color_code, module_id, is_default, sort_order, status)
SELECT '7', '1', 'Partially received', '#f23cfd', '3', '1', '3', '1'
FROM DUAL WHERE NOT EXISTS (SELECT xe_id, store_id, module_id, status_name FROM purchase_order_status  
WHERE xe_id='7' and store_id='1' and module_id='3' and status_name='Partially received');

INSERT INTO purchase_order_status (xe_id, store_id, status_name, color_code, module_id, is_default, sort_order, status)
SELECT '8', '1', 'Received', '#49ce49', '3', '1', '4', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_id, status_name FROM purchase_order_status  
WHERE xe_id='8' and store_id='1' and module_id='3' and status_name='Received');

INSERT INTO purchase_order_status (xe_id, store_id, status_name, color_code, module_id, is_default, sort_order, status)
SELECT '9', '1', 'Pending', '#e4d23d', '3', '1', '2', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_id, status_name FROM purchase_order_status  
WHERE xe_id='9' and store_id='1' and module_id='3' and status_name='Pending');

-- Insert data into po_line_item_status table

INSERT INTO po_line_item_status (xe_id, store_id, status_name, color_code,is_default,sort_order,status)  
SELECT '1', '1', 'Pending', '#fabc02', '1','1', '1' FROM DUAL WHERE NOT EXISTS (SELECT * FROM po_line_item_status WHERE xe_id=1);

INSERT INTO po_line_item_status (xe_id, store_id, status_name, color_code,is_default,sort_order,status)  
SELECT '2', '1', 'Out of stock', '#FF0000', '1','2', '1' FROM DUAL WHERE NOT EXISTS (SELECT * FROM po_line_item_status WHERE xe_id=2);

INSERT INTO po_line_item_status (xe_id, store_id, status_name, color_code,is_default,sort_order,status)  
SELECT '3', '1', 'Received', '#33a952', '1','3', '1' FROM DUAL WHERE NOT EXISTS (SELECT * FROM po_line_item_status WHERE xe_id=3);

INSERT INTO po_line_item_status (xe_id, store_id, status_name, color_code,is_default,sort_order,status)  
SELECT '4', '1', 'PO sent', '#cc8800', '1','4', '0' FROM DUAL WHERE NOT EXISTS (SELECT * FROM po_line_item_status WHERE xe_id=4);

INSERT INTO po_line_item_status (xe_id, store_id, status_name, color_code,is_default,sort_order,status)  
SELECT '5', '1', 'Partially received', '#997300', '1','5', '0' FROM DUAL WHERE NOT EXISTS (SELECT * FROM po_line_item_status WHERE xe_id=5);

/*Table structure for table `po_log` */

CREATE TABLE IF NOT EXISTS `po_log` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `description` text NULL,
  `user_type` enum('admin','customer','agent') NULL COMMENT 'i.e agent data in case of agent assignment  ',
  `user_id` int(11) NULL COMMENT 'i.e agent data in case of agent assignment',
  `created_date` datetime NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



/*Table structure for table `po_internal_note` */

CREATE TABLE IF NOT EXISTS `po_internal_note` ( 
  `xe_id` INT(11) NOT NULL AUTO_INCREMENT , 
  `po_id` INT(11) NOT NULL , 
  `user_type` ENUM('admin','agent') NOT NULL , 
  `user_id` INT(11) NOT NULL , 
  `note` TEXT NULL , 
  `seen_flag` ENUM('0','1') NOT NULL DEFAULT '0', 
  `created_date` TIMESTAMP NOT NULL , 
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `po_internal_note_files` */

CREATE TABLE IF NOT EXISTS `po_internal_note_files` ( 
  `xe_id` INT(11) NOT NULL AUTO_INCREMENT , 
  `note_id` INT(11) NOT NULL ,
  `file` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Add 'po_status' column in orders table*/

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'orders'
        AND table_schema = DATABASE()
        AND column_name IN('po_status')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE orders ADD po_status INT(7) NOT NULL DEFAULT  '0' ;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Data for the table `production_hub_settings` for Production 

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '3', 'synchronize_with_store', 'true', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings 
WHERE store_id='1' and module_id='3' and setting_key='synchronize_with_store');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '3', 'purchase_order_id', '{"prefix":"PO","starting_number":4448,"postfix":"XE"}', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings 
WHERE store_id='1' and module_id='3' and setting_key='purchase_order_id');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '3', 'convert_order', '{"is_enabled":true,"conversion_days":7,"is_order_qty_exception_enabled":true,"order_qty":1,"is_rush_order_exception_enabled":false}', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings 
WHERE store_id='1' and module_id='3' and setting_key='convert_order');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '3', 'last_po_date', '{"date":""}', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings 
WHERE store_id='1' and module_id='3' and setting_key='last_po_date');