/*Table structure for table `backgrounds` */

CREATE TABLE IF NOT EXISTS `backgrounds` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `value` varchar(60) DEFAULT NULL COMMENT 'file_name: files, hexcode: color',
  `price` decimal(6,2) DEFAULT NULL,
  `type` tinyint(1) DEFAULT NULL COMMENT '1: Pattern, 0: Color',
  `store_id` int(4) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
LOCK TABLES `backgrounds` WRITE;
/*!40000 ALTER TABLE `backgrounds` DISABLE KEYS */;
INSERT INTO `backgrounds` (`xe_id`, `name`, `value`, `price`, `type`, `store_id`, `created_at`) VALUES
  (1, 'Background', '202003300117121579.png', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (2, 'Background', '202003300117121579.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (3, 'Background', '202003300117121579_1.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (4, 'Background', '202003300117121579_2.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (5, 'Background', '202003300117121579_1.png', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (6, 'Background', '202003300117121579_3.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (7, 'Background', '202003300117121579_4.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (8, 'Background', '202003300117121579_5.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (9, 'Background', '202003300117121579_6.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (10, 'Background', '202003300117121579_7.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (11, 'Background', '202003300117121579_8.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (12, 'Background', '202003300117121579_9.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (13, 'Background', '202003300117121579_2.png', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (14, 'Background', '202003300117121579_10.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (15, 'Background', '202003300117121579_11.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (16, 'Background', '202003300117121579_12.jpg', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (17, 'Background', '202003300117121579_3.png', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (18, 'Background', '202003300117121579_4.png', 0.00, 1, 1, '2020-03-30 13:17:12'),
  (19, 'Blue', '#3b647c', 0.00, 0, 1, '2020-03-30 13:22:34'),
  (20, 'Brown', '#904141', 0.00, 0, 1, '2020-03-30 13:25:34'),
  (21, 'Pink', '#d55959', 0.00, 0, 1, '2020-03-30 13:25:53'),
  (22, 'Purple', '#b46ae1', 0.00, 0, 1, '2020-03-30 13:26:54'),
  (23, 'Sea Green', '#9bf1c8', 0.00, 0, 1, '2020-03-30 14:09:44'),
  (24, 'Orange', '#ebaa59', 0.00, 0, 1, '2020-03-30 14:11:03'),
  (25, 'Lemon', '#bbcf87', 0.00, 0, 1, '2020-03-30 14:11:50'),
  (26, 'Grey', '#949494', 0.00, 0, 1, '2020-03-30 14:13:54'),
  (27, 'Blue', '#407de9', 0.00, 0, 1, '2020-03-30 14:17:36'),
  (28, 'Green', '#348248', 0.00, 0, 1, '2020-03-30 14:19:16');
/*!40000 ALTER TABLE `backgrounds` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `background_category_rel` */

CREATE TABLE IF NOT EXISTS `background_category_rel` (
  `background_id` bigint(20) DEFAULT NULL,
  `category_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `background_category_rel` WRITE;
/*!40000 ALTER TABLE `background_category_rel` DISABLE KEYS */;
INSERT INTO `background_category_rel` (`background_id`, `category_id`) VALUES
  (1, 19),
  (2, 19),
  (3, 19),
  (4, 19),
  (5, 19),
  (6, 19),
  (7, 19),
  (8, 19),
  (9, 19),
  (10, 19),
  (11, 19),
  (12, 19),
  (13, 19),
  (14, 19),
  (15, 19),
  (16, 19),
  (17, 19),
  (18, 19),
  (19, 19),
  (20, 19),
  (21, 19),
  (22, 19),
  (23, 19),
  (24, 19),
  (25, 19),
  (26, 19),
  (27, 19),
  (28, 19);
/*!40000 ALTER TABLE `background_category_rel` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `background_tag_rel` */

CREATE TABLE IF NOT EXISTS `background_tag_rel` (
  `background_id` bigint(20) DEFAULT NULL,
  `tag_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `background_tag_rel` WRITE;
/*!40000 ALTER TABLE `background_tag_rel` DISABLE KEYS */;
INSERT INTO `background_tag_rel` (`background_id`, `tag_id`) VALUES
  (23, 1),
  (24, 2);
/*!40000 ALTER TABLE `background_tag_rel` ENABLE KEYS */;
UNLOCK TABLES;