/*Table structure for table `templates` */

CREATE TABLE IF NOT EXISTS `templates` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `ref_id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL,
  `description` text DEFAULT NULL,
  `no_of_colors` int(11) DEFAULT NULL,
  `color_hash_codes` varchar(255) DEFAULT NULL,
  `is_easy_edit` tinyint(1) DEFAULT 0,
  `total_used` int(11) DEFAULT 0,
  `store_id` int(4) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `template_tag_rel` */

CREATE TABLE IF NOT EXISTS `template_tag_rel` (
  `template_id` int(11) NOT NULL,
  `tag_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `template_print_profile_rel` */

CREATE TABLE IF NOT EXISTS `template_print_profile_rel` (
  `template_id` int(11) NOT NULL,
  `print_profile_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `template_category_rel` */

CREATE TABLE IF NOT EXISTS `template_category_rel` (
  `template_id` int(11) DEFAULT NULL,
  `category_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Alter table `templates` */
ALTER TABLE `templates` ADD COLUMN `template_index` SMALLINT(4) NULL AFTER `color_hash_codes`;