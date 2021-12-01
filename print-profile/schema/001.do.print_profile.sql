/*Table structure for table `engraved_surfaces` */

CREATE TABLE IF NOT EXISTS `engraved_surfaces` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `surface_name` varchar(60) NOT NULL,
  `engraved_type` enum('image','color') DEFAULT NULL,
  `engrave_type_value` varchar(50) DEFAULT NULL COMMENT 'color code or image url',
  `engrave_preview_type` enum('image','color') DEFAULT NULL,
  `engrave_preview_type_value` varchar(50) DEFAULT NULL,
  `shadow_direction` varchar(30) DEFAULT NULL,
  `shadow_size` varchar(20) DEFAULT NULL,
  `shadow_opacity` varchar(20) DEFAULT NULL,
  `shadow_strength` varchar(30) DEFAULT NULL,
  `shadow_blur` varchar(50) DEFAULT NULL,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `engraved_surfaces` */

INSERT INTO `engraved_surfaces` (`surface_name`, `engraved_type`, `engrave_type_value`, `engrave_preview_type`, `engrave_preview_type_value`, `shadow_direction`, `shadow_size`, `shadow_opacity`, `shadow_strength`, `shadow_blur`, `is_user_defined`) VALUES
('Wooden', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
('Metal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0);

/*Table structure for table `features` */

CREATE TABLE IF NOT EXISTS `features` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_type_id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL,
  `slug` varchar(50) NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `features` */

INSERT INTO `features` (`asset_type_id`, `name`, `slug`) VALUES
(2, 'Clipart library', 'clipart'),
(11, 'Use from Templates', 'template'),
(1, 'Backgrounds', 'background'),
(0, 'Draw with your hand', 'drawing'),
(9, 'Shapes library', 'shape'),
(0, 'Upload design', 'image'),
(0, 'Add Text', 'text'),
(0, 'Team Jearsy', 'team'),
(0, 'VDP', 'vdp'),
(0, 'AR', 'ar'),
(0, 'Gallery', 'gallery');

/*Table structure for table `print_profiles` */

CREATE TABLE IF NOT EXISTS `print_profiles` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(4) NOT NULL,
  `name` varchar(60) NOT NULL,
  `file_name` varchar(50) DEFAULT NULL,
  `description` text,
  `status` tinyint(1) DEFAULT '0',
  `is_vdp_enabled` tinyint(1) DEFAULT '0',
  `vdp_data` text,
  `is_laser_engrave_enabled` tinyint(1) DEFAULT '0',
  `image_settings` text DEFAULT NULL,
  `color_settings` text DEFAULT NULL,
  `order_settings` text DEFAULT NULL,
  `text_settings` text DEFAULT NULL,
  `misc_settings` text DEFAULT NULL,
  `name_number_settings` text DEFAULT NULL,
  `ar_settings` text DEFAULT NULL,
  `is_price_setting` tinyint(1) DEFAULT '0',
  `is_product_setting` tinyint(1) DEFAULT '0',
  `is_assets_setting` tinyint(1) DEFAULT '0',
  `allow_full_color` tinyint(1) DEFAULT '0',
  `is_disabled` tinyint(1) DEFAULT '0',
  `is_draft` tinyint(1) DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `print_profiles` (`xe_id`, `store_id`, `name`, `file_name`, `description`, `status`, `is_vdp_enabled`, `vdp_data`, `is_laser_engrave_enabled`, `image_settings`, `color_settings`, `order_settings`, `text_settings`, `misc_settings`, `name_number_settings`, `ar_settings`, `is_price_setting`, `is_product_setting`, `is_assets_setting`,`allow_full_color` ,`is_disabled`, `is_draft`, `created_at`) VALUES
(1, 1, 'DTG', '202003060207555090.png', 'Unlimited color spectrum.\nNo minimum order quantity.\nRealistic print quality.', 0, 1, '0', 0, '{\"allowed_image_format":[1,2,3,4],"image_processing_type":"quick","is_auto_convert_image":0,"is_more_color_options":1,"color_option_value":8,"is_white_removal":1,"image_effects":{"is_image_mask":1,"is_image_filter":1},"show_color_option":{"grey_scale":1,"one_color":1,"bw":1,"monochrome":1},"auto_convert_image_type":""}', '{"show_color_picker":1,"fill_color_code":"#000000","border_color_code":"#000000","max_colors_allowed":9,"is_max_color_allowed":0}', '{"min_order_qty":1,"is_include_product_image":"exclude","is_horizontally_flip":0,"is_color_separation_enabled":0,"is_min_order_qty":0,"allowed_order_format":[14,15,16],"is_invert_color_enabled":0,"is_bleed_mark_enabled":0,"bleed_mark":{"crop_mark":0,"cut_mark":0}}', '{"do_user_upload_fonts":1,"is_text_stroke":1,"is_multiline_text":1,"do_show_graphic_fonts":1}', '{"allow_user_uplaod_bg":1,"clipart_options":"add","template_options":"replace","shape_options":"add"}', NULL, '{"is_ar_enabled":1,"height":3,"width":3}',0, 0, 0, 0, 0, 1, '2020-03-06 19:37:55'),
(2, 1, 'Screen print', '202003060244143853.png', 'Transfer any artwork on the products.\nNo minimum quantities.\nUnlimited color spectrum.', 0, 0, '0', 0, '{"allowed_image_format":[1,2,3,4],"image_processing_type":"quick","is_auto_convert_image":0,"is_more_color_options":1,"color_option_value":8,"is_white_removal":1,"image_effects":{"is_image_mask":1,"is_image_filter":1},"show_color_option":{"grey_scale":1,"one_color":1,"bw":1,"monochrome":1},"auto_convert_image_type":""}', '{"show_color_picker":1,"fill_color_code":"#000000","border_color_code":"#000000","max_colors_allowed":9,"is_max_color_allowed":0}', '{"min_order_qty":1,"is_include_product_image":"exclude","is_horizontally_flip":0,"is_color_separation_enabled":0,"is_min_order_qty":0,"allowed_order_format":[14,15,16],"is_invert_color_enabled":0,"is_bleed_mark_enabled":0,"bleed_mark":{"crop_mark":0,"cut_mark":0}}', '{"do_user_upload_fonts":1,"is_text_stroke":1,"is_multiline_text":1,"do_show_graphic_fonts":1}', '{"allow_user_uplaod_bg":1,"clipart_options":"add","template_options":"replace","shape_options":"add"}', NULL, '{"is_ar_enabled":1,"height":3,"width":3}',0, 0, 0, 0, 0, 1, '2020-03-06 20:14:14');

/*Table structure for table `print_profile_allowed_formats` */

CREATE TABLE IF NOT EXISTS `print_profile_allowed_formats` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `type` char(6) NOT NULL DEFAULT 'image',
  `is_disabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `print_profile_allowed_formats` */

INSERT INTO `print_profile_allowed_formats` (`xe_id`, `name`, `type`, `is_disabled`) VALUES
(1, 'svg', 'image', 0),
(2, 'jpeg', 'image', 0),
(3, 'jpg', 'image', 0),
(4, 'png', 'image', 0),
(5, 'gif', 'image', 0),
(6, 'bmp', 'image', 0),
(7, 'pdf', 'image', 0),
(8, 'ai', 'image', 0),
(9, 'psd', 'image', 0),
(10, 'eps', 'image', 0),
(11, 'cdr', 'image', 0),
(12, 'dxf', 'image', 0),
(13, 'tif', 'image', 0),
(14, 'pdf', 'order', 0),
(15, 'png', 'order', 0),
(16, 'svg', 'order', 0);

/*Table structure for table `print_profile_assets_category_rel` */

CREATE TABLE IF NOT EXISTS `print_profile_assets_category_rel` (
  `print_profile_id` int(11) NOT NULL,
  `asset_type_id` int(11) NOT NULL,
  `category_id` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `print_profile_assets_category_rel` (`print_profile_id`, `asset_type_id`, `category_id`) VALUES
  (2, 1, '19'),
  (2, 2, '57'),
  (2, 2, '58'),
  (2, 2, '59'),
  (2, 2, '61'),
  (2, 3, '28'),
  (2, 3, '29'),
  (2, 3, '30'),
  (2, 3, '31'),
  (2, 3, '32'),
  (2, 3, '38'),
  (2, 3, '40'),
  (2, 3, '63'),
  (2, 3, '64'),
  (2, 6, '66'),
  (2, 6, '67'),
  (2, 9, '20'),
  (2, 11, '46'),
  (1, 1, '19'),
  (1, 2, '57'),
  (1, 2, '58'),
  (1, 2, '59'),
  (1, 2, '60'),
  (1, 3, '28'),
  (1, 3, '29'),
  (1, 3, '30'),
  (1, 3, '31'),
  (1, 3, '32'),
  (1, 3, '33'),
  (1, 3, '37'),
  (1, 3, '38'),
  (1, 3, '40'),
  (1, 3, '63'),
  (1, 3, '64'),
  (1, 6, '66'),
  (1, 6, '67'),
  (1, 9, '20'),
  (1, 11, '46');
/*Table structure for table `print_profile_engrave_settings` */

CREATE TABLE IF NOT EXISTS `print_profile_engrave_settings` (
  `print_profile_id` int(11) NOT NULL,
  `engraved_surface_id` int(11) DEFAULT '0',
  `is_engraved_surface` tinyint(1) DEFAULT '0',
  `is_auto_convert` tinyint(1) DEFAULT '0',
  `is_hide_color_options` tinyint(1) DEFAULT '0',
  `auto_convert_type` enum('BW','Grayscale') DEFAULT NULL,
  `is_engrave_image` tinyint(1) DEFAULT '0',
  `is_engrave_preview_image` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `print_profile_engrave_settings` (`print_profile_id`, `engraved_surface_id`, `is_engraved_surface`, `is_auto_convert`, `auto_convert_type`, `is_engrave_image`, `is_engrave_preview_image`) VALUES
  (1, 1, 0, 0, 'BW', 0, 0),
  (2, 1, 0, 0, 'BW', 0, 0);
/*Table structure for table `print_profile_feature_rel` */

CREATE TABLE IF NOT EXISTS `print_profile_feature_rel` (
  `print_profile_id` int(11) NOT NULL,
  `feature_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `print_profile_feature_rel` WRITE;
/*!40000 ALTER TABLE `print_profile_feature_rel` DISABLE KEYS */;
INSERT INTO `print_profile_feature_rel` (`print_profile_id`, `feature_id`) VALUES
  (1, 1),
  (1, 2),
  (1, 3),
  (1, 4),
  (1, 5),
  (1, 6),
  (1, 7),
  (1, 9),
  (1, 10),
  (2, 1),
  (2, 2),
  (2, 3),
  (2, 5),
  (2, 6),
  (2, 7);
  /*!40000 ALTER TABLE `print_profile_feature_rel` ENABLE KEYS */;
UNLOCK TABLES;

/*Queries for Print Profile Pricing*/


/*Table structure for table `print_profile_pricings` */

CREATE TABLE IF NOT EXISTS `print_profile_pricings` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `print_profile_id` int(11) NOT NULL,
  `is_white_base` tinyint(1) NOT NULL DEFAULT '0',
  `white_base_type` enum('DLW','DL','') DEFAULT NULL,
  `is_setup_price` tinyint(1) NOT NULL,
  `setup_price` float(5,2) DEFAULT NULL,
  `setup_type_product` enum('per_product','per_product_side') DEFAULT NULL,
  `setup_type_order` enum('single_order','multiple_order') DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `print_profile_pricings` */

CREATE TABLE IF NOT EXISTS `price_modules` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(30) NOT NULL,
  `sort_order_index` int(11) NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `price_modules` */

INSERT  INTO `price_modules`(xe_id,slug,sort_order_index) VALUES 
(1,'setup',1),
(2,'simple-deco',2),
(3,'vdp',3),
(4,'name-number',4),
(5,'artwork',5),
(6,'sleeve',6),
(7,'image',7),
(8,'letter',8),
(9,'clipart',9),
(10,'fonts',10),
(11,'background',11),
(12,'foil',12);

/*Table structure for table `price_module_settings` */

CREATE TABLE IF NOT EXISTS `price_module_settings` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `print_profile_pricing_id` int(11) NOT NULL,
  `price_module_id` int(11) NOT NULL,
  `module_status` tinyint(1) NOT NULL,
  `is_default_price` tinyint(1) DEFAULT '0',
  `is_quote_enabled` tinyint(1) DEFAULT '0',
  `is_advance_price` tinyint(1) DEFAULT '0',
  `advance_price_settings_id` int(11) DEFAULT NULL,
  `is_quantity_tier` tinyint(1) DEFAULT '0',
  `quantity_tier_type` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `price_default_settings` */

CREATE TABLE IF NOT EXISTS `price_default_settings` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `price_module_setting_id` int(11) NOT NULL,
  `price_key` varchar(30) DEFAULT NULL,
  `price_value` float(6,2) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `price_advanced_price_settings` */

CREATE TABLE IF NOT EXISTS `price_advanced_price_settings` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `advanced_price_type` enum('per_color','per_print_area','per_design_area') DEFAULT NULL,
  `no_of_colors_allowed` int(11) DEFAULT NULL,
  `is_full_color` tinyint(1) DEFAULT '0',
  `area_calculation_type` enum('design_area','bound_area') DEFAULT NULL,
  `min_price` float DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `price_tier_values` */

CREATE TABLE IF NOT EXISTS `price_tier_values` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `attribute_type` varchar(20) DEFAULT NULL,
  `price_module_setting_id` int(11) NOT NULL,
  `print_area_index` int(5) DEFAULT NULL,
  `color_index` varchar(30) DEFAULT NULL,
  `print_area_id` int(11) DEFAULT NULL COMMENT 'print area sequence index',
  `range_from` int(11) DEFAULT NULL COMMENT 'square inch value / no fo letters',
  `range_to` int(11) DEFAULT NULL COMMENT 'square inch value/ no fo letters',
  `key_name` varchar(30) DEFAULT NULL COMMENT 'For name&number, vdp, sleeve price key id',
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `price_tier_whitebases` */

CREATE TABLE IF NOT EXISTS `price_tier_whitebases` (
  `price_tier_value_id` bigint(20) DEFAULT NULL,
  `tier_range_id` int(11) DEFAULT NULL,
  `white_base_type` char(5) DEFAULT NULL,
  `price` decimal(6,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `price_tier_quantity_ranges` */

CREATE TABLE IF NOT EXISTS `price_tier_quantity_ranges` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `price_module_setting_id` int(11) NOT NULL,
  `quantity_from` int(8) DEFAULT NULL,
  `quantity_to` int(8) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Queries for Print Profile Attribute Relation*/

/*Table structure for table `print_profile_attribute_rel` */

CREATE TABLE IF NOT EXISTS `print_profile_attribute_rel` (
  `attribute_term_id` varchar(50) DEFAULT NULL,
  `tier_range_id` int(11) DEFAULT NULL,
  `print_profile_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Alter Queries for Print Profile settings*/

ALTER TABLE `print_profiles` ADD COLUMN `embroidery_settings` TEXT NULL AFTER `order_settings`;

/*Update column values for print_profiles*/

UPDATE `print_profiles` SET `embroidery_settings`='{\"is_embroidery_enabled\":0}' WHERE `xe_id`='1';

UPDATE `print_profiles` SET `embroidery_settings`='{\"is_embroidery_enabled\":0}' WHERE `xe_id`='2';