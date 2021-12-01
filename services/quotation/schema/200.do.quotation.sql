-- Table structure for table `production_hub_modules` 

CREATE TABLE IF NOT EXISTS `production_hub_modules` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data for the table `production_hub_modules` 
INSERT INTO production_hub_modules (xe_id, name)
SELECT '1', 'Quotation'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, name FROM production_hub_modules  
where xe_id='1' and name='Quotation');

INSERT INTO production_hub_modules (xe_id, name)
SELECT '2', 'Order'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, name FROM production_hub_modules  
where xe_id='2' and name='Order');

INSERT INTO production_hub_modules (xe_id, name)
SELECT '3', 'Purchase Order'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, name FROM production_hub_modules  
where xe_id='3' and name='Purchase Order');

INSERT INTO production_hub_modules (xe_id, name)
SELECT '4', 'Production'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, name FROM production_hub_modules  
where xe_id='4' and name='Production');

INSERT INTO production_hub_modules (xe_id, name)
SELECT '5', 'Email Setting'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, name FROM production_hub_modules  
where xe_id='5' and name='Email Setting');

-- Table structure for table `production_hub_settings` 

CREATE TABLE IF NOT EXISTS `production_hub_settings` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(4) NOT NULL,
  `module_id` int(4) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `flag` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'Represent value is changed or not',
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data for the table `production_hub_settings` 

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'is_quote_id_enable', 'true', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='is_quote_id_enable');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'quote_id', '{"prefix":"IM","starting_number":1,"postfix":"NXT"}', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='quote_id');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'is_tags_enabled', 'true', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='is_tags_enabled');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'is_artwork_approval_enable', 'true', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='is_artwork_approval_enable');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'is_rush_order_enable', 'false', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='is_rush_order_enable');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'is_resend_quotation_mail', 'true', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='is_resend_quotation_mail');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'is_quote_app_reminder', 'true', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='is_quote_app_reminder');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'is_minimum_payment', 'true', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='is_minimum_payment');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'minimum_payment_percent', '10', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='minimum_payment_percent');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'is_payment_reminder', 'true', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='is_payment_reminder');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'sender_email', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='sender_email');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'company_name', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='company_name');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'color_code', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='color_code');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'phone_number', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='phone_number');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'city', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='city');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'state', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='state');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'state_id', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='state_id');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'country', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='country');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'country_id', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='country_id');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'zip_code', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='zip_code');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'address', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='address');

INSERT INTO production_hub_settings (store_id, module_id, setting_key, setting_value, flag)
SELECT '1', '1', 'company_logo', '', '0'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, setting_key FROM production_hub_settings  
where store_id='1' and module_id='1' and setting_key='company_logo');

-- Table structure for table `production_status` 

CREATE TABLE IF NOT EXISTS `production_status` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(4) NOT NULL,
  `status_name` varchar(20) NOT NULL,
  `color_code` varchar(10) NOT NULL,
  `module_id` INT(11) NOT NULL,
  `is_default` enum('0','1') NOT NULL,
  `sort_order` int(4) NOT NULL,
  `status` enum('1','0') NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data for the table `production_status` 

INSERT INTO production_status (xe_id, store_id, status_name, color_code, module_id, is_default, sort_order, status)
SELECT '1', '1', 'Open', '#23e6dc', '1', '1', '1', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_id, status_name FROM production_status  
where xe_id='1' and store_id='1' and module_id='1' and status_name='Open');

INSERT INTO production_status (xe_id, store_id, status_name, color_code, module_id, is_default, sort_order, status)
SELECT '2', '1', 'Rejected', '#f30d0d', '1', '1', '2', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_id, status_name FROM production_status  
where xe_id='2' and store_id='1' and module_id='1' and status_name='Rejected');

INSERT INTO production_status (xe_id, store_id, status_name, color_code, module_id, is_default, sort_order, status)
SELECT '3', '1', 'Sent', '#49ce49', '1', '1', '3', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_id, status_name FROM production_status  
where xe_id='3' and store_id='1' and module_id='1' and status_name='Sent');

INSERT INTO production_status (xe_id, store_id, status_name, color_code, module_id, is_default, sort_order, status)
SELECT '4', '1', 'Approved', '#e4d23d', '1', '1', '4', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_id, status_name FROM production_status  
where xe_id='4' and store_id='1' and module_id='1' and status_name='Approved');

INSERT INTO production_status (xe_id, store_id, status_name, color_code, module_id, is_default, sort_order, status)
SELECT '5', '1', 'Ordered', '#8c23e6', '1', '1', '5', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_id, status_name FROM production_status  
where xe_id='5' and store_id='1' and module_id='1' and status_name='Ordered');


-- Table structure for table `production_tags` 

CREATE TABLE IF NOT EXISTS `production_tags` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(4) NOT NULL,
  `name` varchar(255) NOT NULL,
  `module_id` INT(11) NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data for the table `production_tags` 
INSERT INTO production_tags (store_id, name, module_id)
SELECT '1', 'rush order', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, name FROM production_tags  
where store_id='1' and module_id='1' and name='rush order');

INSERT INTO production_tags (store_id, name, module_id)
SELECT '1', 'quote', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, name FROM production_tags  
where store_id='1' and module_id='1' and name='quote');

INSERT INTO production_tags (store_id, name, module_id)
SELECT '1', 'new customer', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, name FROM production_tags  
where store_id='1' and module_id='1' and name='new customer');

INSERT INTO production_tags (store_id, name, module_id)
SELECT '1', 'returning customer', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, name FROM production_tags  
where store_id='1' and module_id='1' and name='returning customer');

INSERT INTO production_tags (store_id, name, module_id)
SELECT '1', 'large order', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, name FROM production_tags  
where store_id='1' and module_id='1' and name='large order');


-- Table structure for table `quotations` 

CREATE TABLE IF NOT EXISTS `quotations` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(4) NOT NULL,
  `quote_id` varchar(30) NOT NULL,
  `customer_id` varchar(50) NOT NULL,
  `shipping_id` varchar(50) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `created_by` enum('admin','customer','agent') NOT NULL,
  `created_by_id` varchar(50) NOT NULL,
  `quote_source` enum('admin','tool','form') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ship_by_date` datetime NOT NULL,
  `exp_delivery_date` datetime NOT NULL,
  `is_artwork` enum('0','1') NOT NULL,
  `is_rush` enum('0','1') NOT NULL,
  `rush_type` enum('percentage','flat') DEFAULT NULL,
  `rush_amount` float(10,2) DEFAULT NULL COMMENT 'value depends upon rush_type',
  `discount_type` enum('percentage','flat') DEFAULT NULL,
  `discount_amount` float(10,2) DEFAULT NULL COMMENT 'value depends upon discount_type',
  `shipping_type` enum('express','regular') DEFAULT NULL,
  `shipping_amount` float(10,2) DEFAULT NULL,
  `tax_amount` float(10,2) DEFAULT NULL,
  `design_total` float(10,2) DEFAULT NULL,
  `quote_total` float(10,2) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `draft_flag` ENUM('0','1') NOT NULL DEFAULT '0' COMMENT '0-send to customer,1-save as draft',
  `reject_note` TEXT NULL,
  `invoice_id` VARCHAR(30) DEFAULT NULL,
  `order_id` VARCHAR(50) DEFAULT NULL,
  `request_payment` FLOAT(10,2) DEFAULT NULL,
  `request_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quote_items` 

CREATE TABLE IF NOT EXISTS `quote_items` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` varchar(30) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `artwork_type` enum('uploaded_file','design_tool') NOT NULL,
  `custom_design_id` int(11) DEFAULT NULL,
  `design_cost` float(10,2) NOT NULL,
  `unit_total` float(10,2) NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quote_item_files` 

CREATE TABLE IF NOT EXISTS `quote_item_files` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `side_id` int(11) NOT NULL,
  `decoration_area_id` int(11) NOT NULL,
  `print_method_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `preview_file` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quote_log` 

CREATE TABLE IF NOT EXISTS `quote_log` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `user_type` enum('admin','customer','agent') NOT NULL COMMENT 'i.e agent data in case of agent assignment  ',
  `user_id` int(11) NOT NULL COMMENT 'i.e agent data in case of agent assignment',
  `created_date` datetime NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quote_tag_rel` 

CREATE TABLE IF NOT EXISTS `quote_tag_rel` (
  `quote_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quote_payments` 

CREATE TABLE IF NOT EXISTS `quote_payments` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `payment_amount` float(10,2) NOT NULL,
  `txn_id` varchar(30) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_mode` enum('online','offline','cash','cheque','bank transfer','credit card','others') DEFAULT NULL,
  `payment_status` enum('pending','paid') DEFAULT NULL,
  `note` TEXT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quote_item_variants` 

CREATE TABLE IF NOT EXISTS `quote_item_variants` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `variant_id` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` float(10,2) NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quote_internal_note` 

CREATE TABLE IF NOT EXISTS `quote_internal_note` ( 
  `xe_id` INT(11) NOT NULL AUTO_INCREMENT , 
  `quote_id` INT(11) NOT NULL , 
  `user_type` ENUM('admin','agent') NOT NULL , 
  `user_id` INT(11) NOT NULL , 
  `note` TEXT NULL , 
  `seen_flag` ENUM('0','1') NOT NULL DEFAULT '0', 
  `created_date` TIMESTAMP NOT NULL , 
  PRIMARY KEY (`xe_id`)
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quote_internal_note_files` 

CREATE TABLE IF NOT EXISTS `quote_internal_note_files` ( 
  `xe_id` INT(11) NOT NULL AUTO_INCREMENT , 
  `note_id` INT(11) NOT NULL ,
  `file` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`xe_id`)
) ENGINE = InnoDB DEFAULT CHARSET=latin1;


-- Table structure for table `production_template_abbriviations` 

CREATE TABLE IF NOT EXISTS `production_template_abbriviations` ( 
  `xe_id` int(11) NOT NULL AUTO_INCREMENT , 
  `abbr_name` varchar(70) NOT NULL,
  `module_id` INT(11) NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

-- Data for the table `production_template_abbriviations` 

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{quote_id}','1'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{quote_id}' and module_id='1');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{quote_date}','1'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{quote_date}' and module_id='1');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{customer_name}','1'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{customer_name}' and module_id='1');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{payment_date}','1'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{payment_date}' and module_id='1');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{shipping_date}','1'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{shipping_date}' and module_id='1');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{delivery_date}','1'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{delivery_date}' and module_id='1');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{payment_amount}','1'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{payment_amount}' and module_id='1');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{payment_due_amount}','1'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{payment_due_amount}' and module_id='1');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{public_url}','1'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{public_url}' and module_id='1');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{reject_note}','1'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{reject_note}' and module_id='1');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{request_payment_amount}','1'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{request_payment_amount}' and module_id='1');


-- Table structure for table `production_email_templates` 

CREATE TABLE IF NOT EXISTS `production_email_templates` ( 
  `xe_id` int(11) NOT NULL AUTO_INCREMENT , 
  `store_id` int(11) NOT NULL,
  `module_id` INT(11) NOT NULL,
  `template_type_name` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_configured` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0-Configured, 1-Not Configured',
  PRIMARY KEY (`xe_id`)
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

-- Data for the table `production_email_templates` 

INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1','1','quote_sent', 'Quotation request placed successfully', '<div>Hi {customer_name}</div><div><br></div><div>We have received the quotation request for your order with quotation reference number {quote_id}. You can click on the link below to see the status of your quotation.</div><div><br></div><div>{public_url}</div><div><br></div><div>We will look into your quotation request and get back to you at the earliest.</div>', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates 
where store_id='1' and module_id='1' and template_type_name='quote_sent');

INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1','1','quote_approval', 'Quotation request Approved', '<div>We are glad to inform you that your quotation with reference number {quote_id} has been approved.<br></div><div><br></div><div>You can click on the link below to see the status of your quotation.</div><div><br></div><div>{public_url}</div><div><br></div><div>Thank you for shopping with us.</div>', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates 
where store_id='1' and module_id='1' and template_type_name='quote_approval');

INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1','1','quote_reject', 'Quotation request Declined', '<div>Hi {customer_name}</div><div><br></div><div>We would like to inform you that your quotation with reference number {quote_id} has been declined. We value your time and effort for reaching out to us for a quotation. We are looking forward to provide a better deal to you in the near future.<br></div><div><br></div><div>You can click on the link below to see the status of your quotation.</div><div><br></div><div>{public_url}</div><div><br></div><div>Thank you for shopping with us.</div>', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates 
where store_id='1' and module_id='1' and template_type_name='quote_reject');

INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1','1','convert_to_order', 'Order Received', '<div>Hi {customer_name}</div><div><br></div><div>Your quotation request with reference number {quote_id} has been recieved as order at our production site. The order is expected to be delivered to you by {delivery_date}.<br></div><div><br></div><div>You can click on the link below to see the status of your quotation.</div><div><br></div><div>{public_url}</div><div><br></div><div>Thank you for shopping with us.</div>', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates 
where store_id='1' and module_id='1' and template_type_name='convert_to_order');

INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1','1','request_payment', 'Payment is pending', '<div>Hi {customer_name}</div><div><br></div><div>Your quotation request with reference number {quote_id} has been approved and we are expecting the payment of {request_payment_amount} towards it. The order request will be sent to production after acknowledgement of the successful payment.<br></div><div><br></div><div>You can click on the link below to see the status of your quotation.</div><div><br></div><div>{public_url}</div><div><br></div><div>Thank you for shopping with us.</div>', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates 
where store_id='1' and module_id='1' and template_type_name='request_payment');

INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1','1','receive_payment', 'Payment is Received', '<div>Hi {customer_name}</div><div><br></div><div>We have receieved a payment of {payment_amount} towards your quotation request with reference number {quote_id}. You will be notified soon once the order is placed with the requested items.</div><div><br></div><div>You can click on the link below to see the status of your quotation.</div><div><br></div><div>{public_url}</div><div><br></div><div>Thank you for shopping with us.</div>', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates 
where store_id='1' and module_id='1' and template_type_name='receive_payment');

INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1','1','bulk_resend_quotation', 'Quotation request placed successfully', '<div>Hi</div><div><br></div><div>We have received the quotation request for your order with quotation reference number {quote_id}. You can click on the link below to see the status of your quotation.</div><div><br></div><div>{public_url}</div><div><br></div><div>We will look into your quotation request and get back to you at the earliest.</div>', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates 
where store_id='1' and module_id='1' and template_type_name='bulk_resend_quotation');

INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1','1','bulk_quotation_approval', 'Quotation request Approved', '<div>Hi</div><div><br></div><div>We are glad to inform you that your quotation with reference number {quote_id} has been approved.<br></div><div><br></div><div>You can click on the link below to see the status of your quotation.</div><div><br></div><div>{public_url}</div><div><br></div><div>Thank you for shopping with us.</div>', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates 
where store_id='1' and module_id='1' and template_type_name='bulk_quotation_approval');

INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1','1','bulk_payment_reminder', 'Payment is pending', '<div>Hi</div><div><br></div><div>Your quotation request with reference number {quote_id} has been approved and we are expecting the payment of {request_payment_amount} towards it. The order request will be sent to production after acknowledgement of the successful payment.<br></div><div><br></div><div>You can click on the link below to see the status of your quotation.</div><div><br></div><div>{public_url}</div><div><br></div><div>Thank you for shopping with us.</div>', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates 
where store_id='1' and module_id='1' and template_type_name='bulk_payment_reminder');

-- Table structure for table `quote_payment_settings` 

CREATE TABLE IF NOT EXISTS `quote_payment_methods` ( 
  `xe_id` int(11) NOT NULL AUTO_INCREMENT, 
  `store_id` int(4) DEFAULT NULL,
  `payment_type` varchar(100) NOT NULL,
  `payment_mode` enum('test','live') NOT NULL DEFAULT 'test',
  PRIMARY KEY (`xe_id`)
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

-- Data for the table `quote_payment_methods` 
INSERT INTO quote_payment_methods (store_id, payment_type)
SELECT '1','PayPal'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, payment_type FROM quote_payment_methods 
where store_id='1' and payment_type='PayPal');


-- Table structure for table `quote_payment_settings` 

CREATE TABLE IF NOT EXISTS `quote_payment_settings` ( 
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_method_id` int(11) NOT NULL,
  `keyname` varchar(255) DEFAULT NULL,
  `keyvalue` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

-- Data for the table `quote_payment_settings`
INSERT INTO quote_payment_settings (payment_method_id, keyname)
SELECT '1','merchant_email_id'
FROM DUAL
WHERE NOT EXISTS (SELECT payment_method_id, keyname FROM quote_payment_settings 
where payment_method_id='1' and keyname='merchant_email_id');


-- Table structure for table `quote_conversations` 

CREATE TABLE IF NOT EXISTS `quote_conversations` ( 
  `xe_id` int(11) NOT NULL AUTO_INCREMENT , 
  `quote_id` int(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `user_type` ENUM('admin','agent','customer') NOT NULL,
  `message` TEXT DEFAULT NULL,
  `seen_flag` ENUM('0','1') NOT NULL DEFAULT '1',
  `created_date` DATETIME NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quote_conversation_files` 

CREATE TABLE IF NOT EXISTS `quote_conversation_files` ( 
  `xe_id` INT(11) NOT NULL AUTO_INCREMENT , 
  `conversation_id` INT(11) NOT NULL,
  `file` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quote_dynamic_form_attribute` 

CREATE TABLE IF NOT EXISTS `quote_dynamic_form_attribute` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `input_type` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data for the table `quote_dynamic_form_attribute` 

INSERT INTO quote_dynamic_form_attribute (input_type)
SELECT 'Text'
FROM DUAL
WHERE NOT EXISTS (SELECT input_type FROM quote_dynamic_form_attribute 
where input_type='Text');

INSERT INTO quote_dynamic_form_attribute (input_type)
SELECT 'Email'
FROM DUAL
WHERE NOT EXISTS (SELECT input_type FROM quote_dynamic_form_attribute 
where input_type='Email');

INSERT INTO quote_dynamic_form_attribute (input_type)
SELECT 'Number'
FROM DUAL
WHERE NOT EXISTS (SELECT input_type FROM quote_dynamic_form_attribute 
where input_type='Number');

INSERT INTO quote_dynamic_form_attribute (input_type)
SELECT 'Select option'
FROM DUAL
WHERE NOT EXISTS (SELECT input_type FROM quote_dynamic_form_attribute 
where input_type='Select option');

INSERT INTO quote_dynamic_form_attribute (input_type)
SELECT 'Checkbox'
FROM DUAL
WHERE NOT EXISTS (SELECT input_type FROM quote_dynamic_form_attribute 
where input_type='Checkbox');

INSERT INTO quote_dynamic_form_attribute (input_type)
SELECT 'Radio Button'
FROM DUAL
WHERE NOT EXISTS (SELECT input_type FROM quote_dynamic_form_attribute 
where input_type='Radio Button');

INSERT INTO quote_dynamic_form_attribute (input_type)
SELECT 'Textarea'
FROM DUAL
WHERE NOT EXISTS (SELECT input_type FROM quote_dynamic_form_attribute 
where input_type='Textarea');

INSERT INTO quote_dynamic_form_attribute (input_type)
SELECT 'Upload files'
FROM DUAL
WHERE NOT EXISTS (SELECT input_type FROM quote_dynamic_form_attribute 
where input_type='Upload files');

INSERT INTO quote_dynamic_form_attribute (input_type)
SELECT 'Date Picker'
FROM DUAL
WHERE NOT EXISTS (SELECT input_type FROM quote_dynamic_form_attribute 
where input_type='Date Picker');


-- Table structure for table `quotation_request_form_values` 

CREATE TABLE IF NOT EXISTS `quotation_request_form_values` (
  `quote_id` varchar(30) DEFAULT NULL,
  `form_key` text,
  `form_value` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quotation_request_details` 

CREATE TABLE IF NOT EXISTS `quotation_request_details` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` varchar(30) DEFAULT NULL,
  `product_details` text,
  `design_details` text,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Table structure for table `quote_dynamic_form_values` 

CREATE TABLE IF NOT EXISTS `quote_dynamic_form_values` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) NOT NULL,
  `label_slug` varchar(50) DEFAULT NULL,
  `attribute_id` int(11) NOT NULL,
  `placeholder` varchar(255) DEFAULT NULL,
  `value` text,
  `is_required` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-Optional,1-Required',
  `sort_order` int(5) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



