/*Table structure for table `masks` */

CREATE TABLE IF NOT EXISTS `masks` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `mask_name` varchar(60) DEFAULT NULL,
  `file_name` varchar(60) DEFAULT NULL,
  `store_id` int(4) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `masks` WRITE;
/*!40000 ALTER TABLE `masks` DISABLE KEYS */;
INSERT INTO `masks` (`xe_id`, `name`, `mask_name`, `file_name`, `store_id`, `created_at`) VALUES
  (1, 'Mask', '202003300710438854.svg', '202003300710439245.png', 1, '2020-03-30 19:10:43'),
  (2, 'Image Mask', '202003300711067645.svg', '202003300711064245.png', 1, '2020-03-30 19:11:06'),
  (3, 'Image Mask', '202003300716447500.svg', '202003300716443929.png', 1, '2020-03-30 19:16:44'),
  (4, 'Image Mask', '202003300717197689.svg', '202003300717194262.png', 1, '2020-03-30 19:17:19'),
  (5, 'Image Mask', '202003300717381284.svg', '202003300717385967.png', 1, '2020-03-30 19:17:38'),
  (6, 'Image Mask', '202003300718257831.svg', '20200330071825492.png', 1, '2020-03-30 19:18:25'),
  (7, 'Image Mask', '202003300718519907.svg', '202003300718517714.png', 1, '2020-03-30 19:18:51'),
  (8, 'Image Mask', '202003300719493322.svg', '202003300719494632.png', 1, '2020-03-30 19:19:49'),
  (9, 'Image Mask', '202003300720085306.svg', '202003300720084902.png', 1, '2020-03-30 19:20:08'),
  (10, 'Image Mask', '202003300720303956.svg', '202003300720302447.png', 1, '2020-03-30 19:20:30');
/*!40000 ALTER TABLE `masks` ENABLE KEYS */;
UNLOCK TABLES;
/*Table structure for table `mask_tag_rel` */

CREATE TABLE IF NOT EXISTS `mask_tag_rel` (
  `mask_id` bigint(20) DEFAULT NULL,
  `tag_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `mask_tag_rel` WRITE;
/*!40000 ALTER TABLE `mask_tag_rel` DISABLE KEYS */;
INSERT INTO `mask_tag_rel` (`mask_id`, `tag_id`) VALUES
  (2, 8),
  (3, 8),
  (4, 8),
  (5, 8),
  (7, 8),
  (10, 8);
/*!40000 ALTER TABLE `mask_tag_rel` ENABLE KEYS */;
UNLOCK TABLES;
