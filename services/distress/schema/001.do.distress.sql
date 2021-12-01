/*Table structure for table `distresses` */

CREATE TABLE IF NOT EXISTS `distresses` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `file_name` varchar(60) DEFAULT NULL,
  `store_id` int(4) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


LOCK TABLES `distresses` WRITE;
/*!40000 ALTER TABLE `distresses` DISABLE KEYS */;
INSERT INTO `distresses` (`xe_id`, `name`, `file_name`, `store_id`, `created_at`) VALUES
  (2, 'Distress', '202003300629173054.jpg', 1, '2020-03-30 18:29:17'),
  (3, 'Distress Effect', '202003300630349558.png', 1, '2020-03-30 18:30:34'),
  (4, 'Distress Effect', '202003300630565691.png', 1, '2020-03-30 18:30:56'),
  (5, 'Distress Effect', '202003300631151926.png', 1, '2020-03-30 18:31:15'),
  (8, 'Distress', '202003310848568937.png', 1, '2020-03-31 08:48:56'),
  (9, 'Distress', '202003310849161969.png', 1, '2020-03-31 08:49:16'),
  (10, 'Distress', '202003310849335632.png', 1, '2020-03-31 08:49:33'),
  (11, 'Distress', '202003310849509466.png', 1, '2020-03-31 08:49:50'),
  (12, 'Distress', '202003310850064436.png', 1, '2020-03-31 08:50:06'),
  (13, 'Distress Effect', '202003310850352984.png', 1, '2020-03-31 08:50:35');
/*!40000 ALTER TABLE `distresses` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `distress_tag_rel` */

CREATE TABLE IF NOT EXISTS `distress_tag_rel` (
  `distress_id` bigint(20) DEFAULT NULL,
  `tag_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


LOCK TABLES `distress_tag_rel` WRITE;
/*!40000 ALTER TABLE `distress_tag_rel` DISABLE KEYS */;
INSERT INTO `distress_tag_rel` (`distress_id`, `tag_id`) VALUES
  (2, 6),
  (3, 7),
  (4, 7),
  (5, 7),
  (6, 16),
  (7, 16),
  (7, 6),
  (8, 6),
  (9, 6),
  (10, 6),
  (11, 6),
  (12, 6),
  (13, 7);
/*!40000 ALTER TABLE `distress_tag_rel` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `distress_category_rel` */

CREATE TABLE IF NOT EXISTS `distress_category_rel` (
  `distress_id` bigint(20) DEFAULT NULL,
  `category_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
LOCK TABLES `distress_category_rel` WRITE;
/*!40000 ALTER TABLE `distress_category_rel` DISABLE KEYS */;
INSERT INTO `distress_category_rel` (`distress_id`, `category_id`) VALUES
  (1, 21),
  (2, 21),
  (3, 21),
  (4, 21),
  (5, 21),
  (6, 21),
  (7, 21),
  (8, 21),
  (9, 21),
  (10, 21),
  (11, 21),
  (12, 21),
  (13, 21);
/*!40000 ALTER TABLE `distress_category_rel` ENABLE KEYS */;
UNLOCK TABLES;