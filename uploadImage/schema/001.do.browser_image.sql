
/*Table structure for table `masks` */

CREATE TABLE IF NOT EXISTS `browser_images` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `browser_id` varchar(50) DEFAULT NULL,
  `file_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;