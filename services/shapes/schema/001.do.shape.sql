/*Table structure for table `shapes` */

CREATE TABLE IF NOT EXISTS `shapes` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `file_name` varchar(60) DEFAULT NULL,
  `store_id` int(4) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


LOCK TABLES `shapes` WRITE;
/*!40000 ALTER TABLE `shapes` DISABLE KEYS */;
INSERT INTO `shapes` (`xe_id`, `name`, `file_name`, `store_id`, `created_at`) VALUES
  (1, 'Shapes', '202003300113458330.svg', 1, '2020-03-30 13:13:45'),
  (2, 'Shapes', '202003300113458330_1.svg', 1, '2020-03-30 13:13:45'),
  (3, 'Shapes', '202003300113458330_2.svg', 1, '2020-03-30 13:13:45'),
  (4, 'Shapes', '202003300113458330_3.svg', 1, '2020-03-30 13:13:45'),
  (5, 'Shapes', '202003300113458330_4.svg', 1, '2020-03-30 13:13:45'),
  (6, 'Shapes', '202003300113458330_5.svg', 1, '2020-03-30 13:13:46'),
  (7, 'Shapes', '202003300113458330_6.svg', 1, '2020-03-30 13:13:46'),
  (8, 'Shapes', '202003300113458330_7.svg', 1, '2020-03-30 13:13:46'),
  (9, 'Shapes', '202003300113458330_8.svg', 1, '2020-03-30 13:13:46'),
  (10, 'Shapes', '202003300113458330_9.svg', 1, '2020-03-30 13:13:46'),
  (11, 'Shapes', '202003300113458330_10.svg', 1, '2020-03-30 13:13:46'),
  (12, 'Shapes', '202003300113458330_11.svg', 1, '2020-03-30 13:13:46'),
  (13, 'Shapes', '202003300113458330_12.svg', 1, '2020-03-30 13:13:46'),
  (14, 'Shapes', '202003300113458330_13.svg', 1, '2020-03-30 13:13:46'),
  (15, 'Shapes', '202003300113458330_14.svg', 1, '2020-03-30 13:13:46'),
  (16, 'Shapes', '202003300113458330_15.svg', 1, '2020-03-30 13:13:46'),
  (17, 'Shapes', '202003300113458330_16.svg', 1, '2020-03-30 13:13:46'),
  (18, 'Shapes', '202003300113458330_17.svg', 1, '2020-03-30 13:13:46'),
  (19, 'Shapes', '202003300113458330_18.svg', 1, '2020-03-30 13:13:46'),
  (20, 'Shapes', '202003300113458330_19.svg', 1, '2020-03-30 13:13:46');
/*!40000 ALTER TABLE `shapes` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `shape_tag_rel` */

CREATE TABLE IF NOT EXISTS `shape_tag_rel` (
  `shape_id` bigint(20) DEFAULT NULL,
  `tag_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `shape_category_rel` */

CREATE TABLE IF NOT EXISTS `shape_category_rel` (
  `shape_id` bigint(20) DEFAULT NULL,
  `category_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
LOCK TABLES `shape_category_rel` WRITE;
/*!40000 ALTER TABLE `shape_category_rel` DISABLE KEYS */;
INSERT INTO `shape_category_rel` (`shape_id`, `category_id`) VALUES
  (1, 20),
  (2, 20),
  (3, 20),
  (4, 20),
  (5, 20),
  (6, 20),
  (7, 20),
  (8, 20),
  (9, 20),
  (10, 20),
  (11, 20),
  (12, 20),
  (13, 20),
  (14, 20),
  (15, 20),
  (16, 20),
  (17, 20),
  (18, 20),
  (19, 20),
  (20, 20);
/*!40000 ALTER TABLE `shape_category_rel` ENABLE KEYS */;
UNLOCK TABLES;