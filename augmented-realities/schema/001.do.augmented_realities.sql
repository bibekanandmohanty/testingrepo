/*Table structure for table `augmented_realities` */
CREATE TABLE IF NOT EXISTS `augmented_realities`(
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `file` varchar(100) DEFAULT NULL,
  `html_file` varchar(100) DEFAULT NULL,
  `pattern_file` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
