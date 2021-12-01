
/*Table structure for table `order_logs` */

CREATE TABLE IF NOT EXISTS `order_logs` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(50) DEFAULT NULL,
  `agent_type` varchar(50) DEFAULT NULL,
  `agent_id` varchar(50) DEFAULT NULL,
  `message` text,
  `log_type` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `artwork_status` varchar(50) NULL,
  `store_id` int(4) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `order_log_files` */

CREATE TABLE IF NOT EXISTS `order_log_files` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_log_id` bigint(20) DEFAULT NULL,
  `file_name` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `orders` */

CREATE TABLE IF NOT EXISTS `orders` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(100) NOT NULL,
  `artwork_status` enum('pending','approved','changed','cancelled','rejected') NOT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;