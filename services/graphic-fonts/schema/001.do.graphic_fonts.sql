/*Table structure for table `graphic_fonts` */

CREATE TABLE IF NOT EXISTS `graphic_fonts` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `price` decimal(6,2) DEFAULT NULL,
  `is_letter_style` tinyint(1) NOT NULL DEFAULT '0',
  `is_number_style` tinyint(1) NOT NULL DEFAULT '0',
  `is_special_character_style` tinyint(1) NOT NULL DEFAULT '0',
  `store_id` int(4) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `graphic_fonts` WRITE;
/*!40000 ALTER TABLE `graphic_fonts` DISABLE KEYS */;
INSERT INTO `graphic_fonts` (`xe_id`, `name`, `price`, `is_letter_style`, `is_number_style`, `is_special_character_style`, `store_id`, `created_at`) VALUES
  (1, 'Text FX', 0.00, 1, 1, 0, 1, '2020-03-30 19:31:37'),
  (4, 'Text Style', 0.00, 1, 0, 0, 1, '2020-03-31 10:25:11');
/*!40000 ALTER TABLE `graphic_fonts` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `graphic_font_tag_rel` */

CREATE TABLE IF NOT EXISTS `graphic_font_tag_rel` (
  `graphic_font_id` bigint(20) DEFAULT NULL,
  `tag_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `graphic_font_tag_rel` WRITE;
/*!40000 ALTER TABLE `graphic_font_tag_rel` DISABLE KEYS */;
INSERT INTO `graphic_font_tag_rel` (`graphic_font_id`, `tag_id`) VALUES
  (4, 9);
/*!40000 ALTER TABLE `graphic_font_tag_rel` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `graphic_font_letters` */

CREATE TABLE IF NOT EXISTS `graphic_font_letters` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `graphic_font_id` bigint(20) DEFAULT NULL,
  `name` varchar(10) DEFAULT NULL,
  `file_name` varchar(50) DEFAULT NULL,
  `font_type` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `graphic_font_letters` WRITE;
/*!40000 ALTER TABLE `graphic_font_letters` DISABLE KEYS */;
INSERT INTO `graphic_font_letters` (`xe_id`, `graphic_font_id`, `name`, `file_name`, `font_type`) VALUES
  (1, 1, 'a', '202003300731373682.svg', 'letter'),
  (2, 1, '0', NULL, 'number'),
  (5, 4, 'A', '202003311025119118.svg', 'letter');
/*!40000 ALTER TABLE `graphic_font_letters` ENABLE KEYS */;
UNLOCK TABLES;