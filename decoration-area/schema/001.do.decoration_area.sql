/*Table structure for table `print_area_types` */

CREATE TABLE IF NOT EXISTS `print_area_types` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `file_name` varchar(60) DEFAULT NULL,
  `is_custom` tinyint(1) DEFAULT '0',
  `store_id` int(4) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


/*Data for the table `print_area_types` */

INSERT  INTO `print_area_types`(`xe_id`,`store_id`,`name`,`file_name`,`is_custom`) 
VALUES (1,1,'Rectangle','rectangle.svg',0),
(2,1,'Square','square.svg',0),
(3,1,'Circle','circle.svg',0),
(4,1,'Triangle','tringle.svg',0),
(5,1,'Elipse','ellipse.svg',0),
(6,1,'Custom',NULL,1);

/*Table structure for table `print_areas` */

CREATE TABLE IF NOT EXISTS `print_areas` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `print_area_type_id` int(11) NOT NULL,
  `file_name` varchar(60) DEFAULT NULL,
  `width` float(5,2) NOT NULL,
  `height` float(5,2) NOT NULL,
  `price` decimal(6,2) DEFAULT NULL,
  `is_user_defined` enum('0','1') NOT NULL DEFAULT '0',
  `is_default` enum('0','1') NOT NULL DEFAULT '0',
  `store_id` int(4) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `print_areas` */

INSERT  INTO `print_areas`(`store_id`,`name`,`print_area_type_id`,`file_name`,`width`,`height`,`is_user_defined`,`is_default`,`price`) 
VALUES (1,'A1',1,NULL,33.11,23.39,'0','0',0.00),
(1,'A2',1,NULL,23.39,16.54,'0','0',0.00),
(1,'A3',1,NULL,16.54,11.69,'0','0',0.00),
(1,'A4',1,NULL,11.69,8.27,'0','0',0.00),
(1,'A5',1,NULL,8.27,5.83,'0','0',0.00),
(1,'A6',1,NULL,5.83,4.13,'0','0',0.00),
(1,'A7',1,NULL,4.13,2.91,'0','0',0.00),
(1,'A8',1,NULL,2.91,2.05,'0','0',0.00)