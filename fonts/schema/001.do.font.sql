
/*Table structure for table `fonts` */

CREATE TABLE IF NOT EXISTS `fonts` (
  `xe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `price` decimal(6,2) DEFAULT NULL,
  `font_family` varchar(60) DEFAULT NULL,
  `file_name` varchar(50) DEFAULT NULL,
  `total_used` int(11) DEFAULT 0,
  `store_id` int(4) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `fonts` WRITE;
/*!40000 ALTER TABLE `fonts` DISABLE KEYS */;
INSERT INTO `fonts` (`xe_id`, `name`, `price`, `font_family`, `file_name`, `total_used`, `store_id`, `created_at`) VALUES
  (26, 'akaPosse', 0.00, 'akaPosse', '202003310925011161.ttf', 0, 1, '2020-03-31 09:25:01'),
  (27, 'Alpha_Echo', 0.00, 'Alpha Echo', '202003310925265354.ttf', 0, 1, '2020-03-31 09:25:26'),
  (28, 'Anton', 0.00, 'Anton', '202003310926045250.ttf', 0, 1, '2020-03-31 09:26:04'),
  (29, 'Averia Libre', 0.00, 'Averia Libre', '202003310926304601.ttf', 0, 1, '2020-03-31 09:26:30'),
  (30, 'BEBAS___', 0.00, 'Bebas', '202003310926531715.ttf', 0, 1, '2020-03-31 09:26:53'),
  (31, 'Baloo', 0.00, 'Baloo', '202003310927225801.ttf', 0, 1, '2020-03-31 09:27:22'),
  (32, 'Bungee', 0.00, 'Bungee', '202003310929169703.ttf', 0, 1, '2020-03-31 09:29:16'),
  (33, 'CargoCrate', 0.00, 'CargoCrate', '202003310930204749.ttf', 0, 1, '2020-03-31 09:30:20'),
  (34, 'Concert One', 0.00, 'Concert One', '202003310931204507.ttf', 0, 1, '2020-03-31 09:31:20'),
  (35, 'Coiny', 0.00, 'Coiny', '202003310932003155.ttf', 0, 1, '2020-03-31 09:32:00'),
  (36, 'Damion', 0.00, 'Damion', '202003310933007365.ttf', 0, 1, '2020-03-31 09:33:00'),
  (37, 'Chango', 0.00, 'Chango', '20200331093410121.ttf', 0, 1, '2020-03-31 09:34:10'),
  (38, 'Chela_One', 0.00, 'Chela One', '202003310934373497.ttf', 0, 1, '2020-03-31 09:34:37'),
  (39, 'Coda', 0.00, 'Coda', '202003310935105799.ttf', 0, 1, '2020-03-31 09:35:10'),
  (40, 'Concert_One', 0.00, 'Concert One', '20200331093542313.ttf', 0, 1, '2020-03-31 09:35:42'),
  (41, 'Cousine', 0.00, 'Cousine', '202003310936266388.ttf', 0, 1, '2020-03-31 09:36:26'),
  (42, 'Emblema One', 0.00, 'Emblema One', '202003310937494491.ttf', 0, 1, '2020-03-31 09:37:49'),
  (43, 'Erica_One', 0.00, 'Erica One', '202003310938179303.ttf', 0, 1, '2020-03-31 09:38:17'),
  (44, 'Expletus Sans', 0.00, 'Expletus Sans', '202003310938424099.ttf', 0, 1, '2020-03-31 09:38:42'),
  (45, 'Fruktur', 0.00, 'Fruktur', '202003310939502704.ttf', 0, 1, '2020-03-31 09:39:50'),
  (46, 'German Beauty', 0.00, 'German Beauty', '202003310940145872.ttf', 0, 1, '2020-03-31 09:40:14'),
  (47, 'Goblin_One', 0.00, 'Goblin One', '202003310941254635.ttf', 0, 1, '2020-03-31 09:41:25'),
  (48, 'Kavoon', 0.00, 'Kavoon', '20200331094256611.ttf', 0, 1, '2020-03-31 09:42:56'),
  (49, 'Luckiest Guy', 0.00, 'Luckiest Guy', '202003310943504592.ttf', 0, 1, '2020-03-31 09:43:50'),
  (50, 'McLaren', 0.00, 'McLaren', '202003310944322269.ttf', 0, 1, '2020-03-31 09:44:32'),
  (51, 'Titan One', 0.00, 'Titan One', '202003310946125894.ttf', 0, 1, '2020-03-31 09:46:12');
/*!40000 ALTER TABLE `fonts` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `font_tag_rel` */

CREATE TABLE IF NOT EXISTS `font_tag_rel` (
  `font_id` bigint(20) DEFAULT NULL,
  `tag_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
LOCK TABLES `font_tag_rel` WRITE;
/*!40000 ALTER TABLE `font_tag_rel` DISABLE KEYS */;
INSERT INTO `font_tag_rel` (`font_id`, `tag_id`) VALUES
  (17, 12),
  (17, 13),
  (18, 14),
  (19, 15),
  (25, 17),
  (26, 18),
  (28, 19),
  (29, 18),
  (30, 20),
  (31, 18),
  (32, 18),
  (33, 18),
  (34, 18),
  (35, 18),
  (36, 18),
  (37, 18),
  (38, 18),
  (39, 18),
  (40, 18),
  (41, 18),
  (42, 18),
  (43, 18),
  (44, 18),
  (45, 18),
  (46, 18),
  (47, 18),
  (48, 18),
  (49, 18),
  (50, 18),
  (51, 18);
/*!40000 ALTER TABLE `font_tag_rel` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `font_category_rel` */

CREATE TABLE IF NOT EXISTS `font_category_rel` (
  `font_id` bigint(20) DEFAULT NULL,
  `category_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
LOCK TABLES `font_category_rel` WRITE;
/*!40000 ALTER TABLE `font_category_rel` DISABLE KEYS */;


INSERT INTO `font_category_rel` (`font_id`, `category_id`) VALUES
  (10, 49),
  (10, 50),
  (17, 48),
  (17, 49),
  (17, 50),
  (17, 51),
  (18, 51),
  (18, 50),
  (19, 48),
  (19, 49),
  (19, 50),
  (19, 51),
  (26, 66),
  (27, 66),
  (28, 66),
  (29, 66),
  (30, 66),
  (31, 66),
  (32, 66),
  (33, 66),
  (34, 66),
  (35, 66),
  (36, 67),
  (37, 67),
  (38, 67),
  (39, 67),
  (40, 67),
  (41, 67),
  (42, 67),
  (43, 67),
  (44, 67),
  (45, 67),
  (46, 67),
  (47, 67),
  (48, 67),
  (49, 67),
  (50, 67),
  (51, 67);
  /*!40000 ALTER TABLE `font_category_rel` ENABLE KEYS */;
UNLOCK TABLES;