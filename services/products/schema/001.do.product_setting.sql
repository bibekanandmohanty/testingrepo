/*Table structure for table `product_settings` */

CREATE TABLE IF NOT EXISTS `product_settings` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` varchar(50) NOT NULL,
  `store_id` int(4) DEFAULT NULL,
  `is_variable_decoration` tinyint(1) DEFAULT '0',
  `is_ruler` tinyint(1) DEFAULT '0',
  `is_crop_mark` tinyint(1) NOT NULL DEFAULT '0',
  `is_safe_zone` tinyint(1) NOT NULL DEFAULT '0',
  `crop_value` decimal(6,2) NOT NULL,
  `safe_value` decimal(6,2) NOT NULL,
  `is_3d_preview` tinyint(1) NOT NULL DEFAULT '0',
  `3d_object_file` varchar(60) DEFAULT NULL,
  `3d_object` text,
  `is_configurator` tinyint(1) DEFAULT '0',
  `scale_unit_id` int(11) NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `product_images` */

CREATE TABLE IF NOT EXISTS `product_images` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `is_disable` tinyint(1) DEFAULT '0',
  `store_id` int(4) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `product_images` (`xe_id`, `name`, `is_disable`, `store_id`) VALUES
  (1, 'tshirt', 0, 1);

/*Table structure for table `product_image_sides` */

CREATE TABLE IF NOT EXISTS `product_image_sides` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_image_id` int(11) NOT NULL,
  `side_name` varchar(30) NULL,
  `sort_order` int(11) NOT NULL,
  `file_name` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `product_image_sides` (`xe_id`, `product_image_id`, `side_name`, `sort_order`, `file_name`) VALUES
  (1, 1, 'front', 1, '20200401114920544.png'),
  (2, 1, 'back', 2, '202004011149206852.png'),
  (3, 1, 'left', 3, '202004011149205955.png'),
  (4, 1, 'right', 4, '202004011149201730.png');

/*Table structure for table `product_image_settings_rel` */

CREATE TABLE IF NOT EXISTS `product_image_settings_rel` (
  `product_setting_id` int(11) NOT NULL,
  `product_image_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `product_sides` */

CREATE TABLE IF NOT EXISTS `product_sides` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_setting_id` int(11) NOT NULL,
  `side_name` varchar(50) NOT NULL,
  `side_index` int(11) DEFAULT NULL,
  `product_image_dimension` text NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `product_image_side_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


/*Table structure for table `product_decoration_settings` */

CREATE TABLE IF NOT EXISTS `product_decoration_settings` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_setting_id` int(11) NOT NULL,
  `product_side_id` int(11) DEFAULT NULL,
  `name` varchar(60) DEFAULT NULL,
  `dimension` text,
  `print_area_id` bigint(20) NOT NULL,
  `sub_print_area_type` enum('normal_size','custom_size','associate_size_variant') NOT NULL DEFAULT 'normal_size',
  `pre_defined_dimensions` varchar(100) DEFAULT NULL,
  `user_defined_dimensions` varchar(100) DEFAULT NULL,
  `custom_min_height` float(5,2) DEFAULT NULL,
  `custom_max_height` float(5,2) DEFAULT NULL,
  `custom_min_width` float(5,2) DEFAULT NULL,
  `custom_max_width` float(5,2) DEFAULT NULL,
  `custom_bound_price` float(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Price per square unit',
  `is_border_enable` tinyint(1) NOT NULL DEFAULT '0',
  `is_sides_allow` tinyint(1) NOT NULL DEFAULT '0',
  `no_of_sides` int(11) NOT NULL DEFAULT '0',
  `is_dimension_enable` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `print_profile_decoration_setting_rel` */

CREATE TABLE IF NOT EXISTS `print_profile_decoration_setting_rel` (
  `print_profile_id` int(11) NOT NULL,
  `decoration_setting_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `print_profile_product_setting_rel` */

CREATE TABLE IF NOT EXISTS `print_profile_product_setting_rel` (
  `print_profile_id` int(11) NOT NULL,
  `product_setting_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `product_size_variant_decoration_settings` */

CREATE TABLE IF NOT EXISTS `product_size_variant_decoration_settings` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `decoration_setting_id` int(11) NOT NULL,
  `print_area_id` bigint(20) DEFAULT NULL,
  `size_variant_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `decoration_objects` */

CREATE TABLE IF NOT EXISTS `decoration_objects` (
  `product_id` varchar(50) NOT NULL,
  `3d_object_file` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;