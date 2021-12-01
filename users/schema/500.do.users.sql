-- Add new column is_default in user_roles table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'user_roles'
        AND table_schema = DATABASE()
        AND column_name IN('is_default')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE user_roles ADD is_default TINYINT(1) NULL DEFAULT '0' COMMENT '0-not default, 1-default';"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new row in user_roles table

INSERT INTO `user_roles` (`store_id`, `role_name`, `is_default`)
SELECT '1' ,'Operator', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, role_name FROM user_roles 
where store_id='1' and role_name='Operator');


UPDATE `user_roles` SET `is_default` = 1 WHERE `xe_id` = 1;


-- Add new row in user_privileges table
INSERT INTO `user_privileges` (`xe_id`, `module_name`, `store_id`, `status`)
SELECT '7', 'Customers' ,'1', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_name FROM user_privileges 
where xe_id=7 and store_id='1' and module_name='Customers');


INSERT INTO `user_privileges` (`xe_id`, `module_name`, `store_id`, `status`)
SELECT '8', 'Quotation' ,'1', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_name FROM user_privileges 
where xe_id=8 and store_id='1' and module_name='Quotation');

INSERT INTO `user_privileges` (`xe_id`, `module_name`, `store_id`, `status`)
SELECT '9', 'Vendor and Purchase order' ,'1', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_name FROM user_privileges 
where xe_id=9 and store_id='1' and module_name='Vendor and Purchase order');


INSERT INTO `user_privileges` (`xe_id`, `module_name`, `store_id`, `status`)
SELECT '10', 'Orders' ,'1', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_name FROM user_privileges 
where xe_id=10 and store_id='1' and module_name='Orders');

INSERT INTO `user_privileges` (`xe_id`, `module_name`, `store_id`, `status`)
SELECT '11', 'Production' ,'1', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT xe_id, store_id, module_name FROM user_privileges 
where xe_id=11 and store_id='1' and module_name='Production');


UPDATE `user_privileges` SET `status` = '0' WHERE `module_name` = 'Production Hub';

-- Table structure for table `privileges_sub_modules` 

CREATE TABLE IF NOT EXISTS `privileges_sub_modules`(
    `xe_id` int(11) NOT NULL AUTO_INCREMENT,
    `user_privilege_id` int(10) NOT NULL,
    `type` varchar(255) NOT NULL COMMENT 'Operator, Agent',
    `slug` varchar(255) NOT NULL,
    `comments` TEXT NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT '1',
    PRIMARY KEY(`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Add new row in privileges_sub_modules table
INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '7', 'Manage customers', 'manage_customer'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='7' and type='Manage customers' and slug='manage_customer');


INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '7', 'Send promotional emails', 'send_promotional_emails'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='7' and type='Send promotional emails' and slug='send_promotional_emails');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '8', 'Manage quotation', 'manage_quotation'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='8' and type='Manage quotation' and slug='manage_quotation');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '8', 'Re-assign option', 'reassign_option'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='8' and type='Re-assign option' and slug='reassign_option');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '9', 'Manage vendors', 'manage_vendors'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='9' and type='Manage vendors' and slug='manage_vendors');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '9', 'Manage PO', 'manage_po'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='9' and type='Manage PO' and slug='manage_po');


INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '11', 'Change due date', 'change_due_date'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='11' and type='Change due date' and slug='change_due_date');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '11', 'Mark as done', 'mark_as_done'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='11' and type='Mark as done' and slug='mark_as_done');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '1', 'Import catalog', 'import_catalog'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='1' and type='Import catalog' and slug='import_catalog');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '1', 'Create decorated product', 'create_decorated_product'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='1' and type='Create decorated product' and slug='create_decorated_product');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '1', 'Decoration Settings', 'product_decoration_settings'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='1' and type='Decoration Settings' and slug='product_decoration_settings');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '2', 'Clipart', 'clipart'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='2' and type='Clipart' and slug='clipart');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '2', 'Backgrounds', 'background'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='2' and type='Backgrounds' and slug='background');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '2', 'Shapes', 'shapes'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='2' and type='Shapes' and slug='shapes');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '2', 'Printable colors', 'colors'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='2' and type='Printable colors' and slug='colors');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '2', 'Fonts', 'fonts'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='2' and type='Fonts' and slug='fonts');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '2', 'Graphic font', 'graphic-font'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='2' and type='Graphic font' and slug='graphic-font');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '2', 'Image masks', 'image-mask'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='2' and type='Image masks' and slug='image-mask');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '2', 'Templates', 'template'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='2' and type='Templates' and slug='template');


INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '4', 'General', 'general'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='3' and type='General' and slug='general');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '4', 'Template on products', 'template-on-products'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='4' and type='Template on products' and slug='template-on-products');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '4', 'Appearance', 'appearance'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='4' and type='Appearance' and slug='appearance');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '4', 'Image', 'image'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='4' and type='Image' and slug='image');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '4', 'Store', 'store'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='4' and type='Store' and slug='store');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '4', 'Cart', 'cart'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='4' and type='Cart' and slug='cart');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '4', 'Order', 'orders'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='4' and type='Order' and slug='orders');

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '4', 'Language', 'language'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='4' and type='Language' and slug='language');


-- Add new column xe_id in user_role_privileges_rel table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'user_role_privileges_rel'
        AND table_schema = DATABASE()
        AND column_name IN('xe_id')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE user_role_privileges_rel ADD `xe_id` INT(10) NOT NULL AUTO_INCREMENT , ADD PRIMARY KEY (`xe_id`);"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;



-- Table structure for table `privileges_sub_modules_rel` 

CREATE TABLE IF NOT EXISTS `privileges_sub_modules_rel`(
    `privilege_rel_id` int(11) NOT NULL,
    `privileges_sub_module_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- Table structure for table `user_module_privilege` 

CREATE TABLE IF NOT EXISTS `user_module_privilege_rel`(
    `xe_id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `role_id` int(11) NOT NULL,
    `role_type` varchar(255) NOT NULL COMMENT 'Operator, Agent',
    `privilege_id` int(10) NOT NULL,
    PRIMARY KEY(`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- Table structure for table `user_sub_module_privilege_rel` 

CREATE TABLE IF NOT EXISTS `user_sub_module_privilege_rel`(
    `xe_id` int(11) NOT NULL AUTO_INCREMENT,
    `user_module_privilege_id` int(11) NOT NULL,
    `action_id` int(11) NOT NULL,
    PRIMARY KEY(`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;






