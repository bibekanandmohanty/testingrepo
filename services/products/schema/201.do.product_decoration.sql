/*Table structure for table `product_settings_rel` */
CREATE TABLE IF NOT EXISTS `product_settings_rel` (
  `product_setting_id` int(11) NOT NULL,
  `product_id` varchar(250) NOT NULL,
  `is_3d_preview` tinyint(1) NOT NULL DEFAULT '0',
  `is_product_image` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `product_category_settings_rel` */
CREATE TABLE IF NOT EXISTS `product_category_settings_rel` (
  `product_setting_id` int(11) NOT NULL,
  `product_category_id` varchar(250) NOT NULL,
  `is_3d_preview` tinyint(1) NOT NULL DEFAULT '0',
  `is_product_image` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;