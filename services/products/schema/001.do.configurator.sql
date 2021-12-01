/*Table structure for table `product_sections` */

CREATE TABLE IF NOT EXISTS `product_sections` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_id` varchar(50) DEFAULT NULL,
  `name` varchar(60) DEFAULT NULL,
  `parent_id` bigint(20) DEFAULT NULL,
  `sort_order` bigint(20) DEFAULT NULL,
  `is_disable` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `product_section_images` */

CREATE TABLE IF NOT EXISTS `product_section_images` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `section_id` bigint(20) DEFAULT NULL,
  `name` varchar(60) DEFAULT NULL,
  `description` text,
  `thumb_value` varchar(60) DEFAULT NULL,
  `price` decimal(6,2) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `file_name` varchar(60) DEFAULT NULL,
  `is_disable` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;