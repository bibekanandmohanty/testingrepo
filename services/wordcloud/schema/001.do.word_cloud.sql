
/*Table structure for table `word_clouds` */

CREATE TABLE IF NOT EXISTS `word_clouds` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `file_name` varchar(60) DEFAULT NULL,
  `store_id` int(4) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `word_clouds` WRITE;
/*!40000 ALTER TABLE `word_clouds` DISABLE KEYS */;
INSERT INTO `word_clouds` (`xe_id`, `name`, `file_name`, `store_id`, `created_at`) VALUES
  (2, 'Word CLoud', '202003311017362243.png', 1, '2020-03-31 10:17:36'),
  (3, 'Word Cloud', '20200331101855521.png', 1, '2020-03-31 10:18:55'),
  (4, 'Word Cloud', '202003311019314430.png', 1, '2020-03-31 10:19:31'),
  (5, 'Word Cloud', '202003311020073064.png', 1, '2020-03-31 10:20:07'),
  (6, 'Shapes', '202003311020263641.png', 1, '2020-03-31 10:20:26'),
  (7, 'Heart', '202003311020564536.png', 1, '2020-03-31 10:20:56'),
  (8, 'Arrow', '20200331102120415.png', 1, '2020-03-31 10:21:20'),
  (9, 'Circle', '20200331102137630.png', 1, '2020-03-31 10:21:37'),
  (10, 'Rectangle', '202003311021546845.png', 1, '2020-03-31 10:21:55'),
  (11, 'Polygon', '20200331102210581.png', 1, '2020-03-31 10:22:10'),
  (12, 'Oval', '202003311022279060.png', 1, '2020-03-31 10:22:27'),
  (13, 'Star', '202003311022477408.png', 1, '2020-03-31 10:22:47');
/*!40000 ALTER TABLE `word_clouds` ENABLE KEYS */;
UNLOCK TABLES;

CREATE TABLE IF NOT EXISTS `word_cloud_tag_rel` (
  `word_cloud_id` bigint(20) DEFAULT NULL,
  `tag_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `word_cloud_tag_rel` WRITE;
/*!40000 ALTER TABLE `word_cloud_tag_rel` DISABLE KEYS */;
INSERT INTO `word_cloud_tag_rel` (`word_cloud_id`, `tag_id`) VALUES
  (1, 21),
  (2, 22),
  (3, 22),
  (4, 22),
  (5, 23),
  (6, 24),
  (7, 25),
  (8, 26),
  (9, 27),
  (10, 28),
  (11, 29),
  (12, 30),
  (13, 31);
/*!40000 ALTER TABLE `word_cloud_tag_rel` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `word_cloud_category_rel` */

CREATE TABLE IF NOT EXISTS `word_cloud_category_rel` (
  `word_cloud_id` bigint(20) DEFAULT NULL,
  `category_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `word_cloud_category_rel` WRITE;
/*!40000 ALTER TABLE `word_cloud_category_rel` DISABLE KEYS */;
INSERT INTO `word_cloud_category_rel` (`word_cloud_id`, `category_id`) VALUES
  (2, 68),
  (3, 68),
  (4, 68),
  (5, 68),
  (6, 68),
  (7, 68),
  (8, 68),
  (9, 68),
  (10, 68),
  (11, 68),
  (12, 68),
  (13, 68);
/*!40000 ALTER TABLE `word_cloud_category_rel` ENABLE KEYS */;
UNLOCK TABLES;