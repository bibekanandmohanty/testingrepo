/*Table structure for table `user_privileges` */

CREATE TABLE IF NOT EXISTS `user_privileges`(
    `xe_id` int(11) NOT NULL AUTO_INCREMENT,
    `module_name` varchar(60) NOT NULL,
    `store_id` int(4) NULL,
    `status` ENUM('0', '1') NOT NULL DEFAULT '1',
    PRIMARY KEY(`xe_id`)
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `user_privileges` */
LOCK TABLES `user_privileges` WRITE;
/*!40000 ALTER TABLE `user_privileges` DISABLE KEYS */;
INSERT INTO `user_privileges` VALUES (1,'Products',1,'1'),(2,'Assets',1,'1'),(3,'Print profile',1,'1'),(4,'Global settings',1,'1'),(5,'Orders',1,'1'),(6,'Users',1,'0');
/*!40000 ALTER TABLE `user_privileges` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `user_privileges_rel` */

CREATE TABLE `user_privileges_rel` (
  `user_id` int(11) NOT NULL,
  `privilege_id` int(11) NOT NULL,
  `privilege_type` text COMMENT 'vallue = all/{''view'':1, ''create'':0, ''update'':0, ''delete'':0}'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


/*Table structure for table `user_roles` */

CREATE TABLE IF NOT EXISTS `user_roles`(
    `xe_id` int(10) NOT NULL AUTO_INCREMENT,
    `role_name` varchar(100) NOT NULL,
    `store_id` int(4) NULL,
    PRIMARY KEY(`xe_id`)
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `user_roles` */

INSERT INTO  `user_roles` (`xe_id`, `store_id`, `role_name`)  
SELECT '1', '1', 'Super Admin' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `user_roles` WHERE xe_id=1);

/*Table structure for table `user_role_privileges_rel` */

CREATE TABLE IF NOT EXISTS `user_role_privileges_rel`(
    `role_id` int(10) NOT NULL,
    `privilege_id` int(10) NOT NULL,
    `privilege_type` text NULL DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `admin_users` */

CREATE TABLE IF NOT EXISTS `admin_users`(
    `xe_id` int(10) NOT NULL AUTO_INCREMENT,
    `name` varchar(60) DEFAULT NULL,
    `email` varchar(60) DEFAULT NULL,
    `password` varchar(100) DEFAULT NULL,
    `question_id` int(4) DEFAULT NULL,
    `answer` text,
    `avatar` varchar(100) DEFAULT NULL,
    `store_id` int(4) NOT NULL DEFAULT '1',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`xe_id`)
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `security_questions` (
  `xe_id` int(4) NOT NULL AUTO_INCREMENT,
  `question` text,
  `store_id` int(4) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `security_questions` (`xe_id`, `question`, `store_id`) VALUES
	(1, 'What was your first pet?', 1),
	(2, 'What was the model of your first car?', 1),
	(3, 'In what city were you born?', 1),
	(4, 'What was your childhood nickname?', 1);
/*Table structure for table `user_role_rel` */

CREATE TABLE IF NOT EXISTS `user_role_rel`(
    `user_id` int(10) NOT NULL,
    `role_id` int(10) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `user_role_rel` (`user_id`, `role_id`) VALUES ('1', '1');

/*Table structure for table `user_privileges_rel` */

CREATE TABLE IF NOT EXISTS `user_privileges_rel`(
    `user_id` int(10) NOT NULL,
    `privilege_id` int(10) NOT NULL,
    `privilege_type` TEXT NULL DEFAULT NULL COMMENT 'vallue = all/{\'view\':1, \'create\':0, \'update\':0, \'delete\':0}'
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

