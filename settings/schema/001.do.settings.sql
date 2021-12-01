/*Table structure for table `app_currency` */

CREATE TABLE IF NOT EXISTS `app_currency` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `code` varchar(10) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `app_currency` */

INSERT INTO `app_currency` (`name`, `symbol`, `code`, `is_default`) VALUES
('US Dollar ', '$', 'USD', 1),
('Indian Rup', 'â¹', 'INR', 0),
('Czech Koru', 'KÄ', 'CZK.', 0),
('Mexican Pe', '$', 'MXN.', 0),
('Japanese Y', 'Â¥', 'JPY.', 0),
('Canadian D', '$', 'CAD', 0),
('New Taiwan', 'NT', 'TWD', 0),
('Danish Kro', 'sk', 'DKK', 0),
('Philippine', 'â±', 'PHP', 0),
('Thai Baht', 'à¸¿', 'THB', 0),
('Russian Ru', 'RU', 'RUB', 0),
('Israeli Ne', 'âª', 'ILS', 0),
('British Po', 'Â£', 'GBP', 0),
('Norwegian', 'kr', 'NOK', 0),
('Euro', 'â¬', 'EUR', 0),
('Australian', '$', 'AUD', 0),
('Polish Zlo', 'zÅ', 'PLN', 0),
('Swiss Fran', 'CH', 'CHF', 0),
('New Zealan', '$', 'AUD', 0),
('Singapore', '$', 'SGD', 0),
('Swedish Kr', 'kr', 'SEK', 0),
('Hong Kong', '$', 'HKD', 0),
('Hungarian', 'Ft', 'HUF', 0),
('Chinese yu', 'Â¥', 'CNY', 0);

/*Table structure for table `app_units` */

CREATE TABLE IF NOT EXISTS `app_units` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `app_units` */

INSERT INTO `app_units`(`name`,`is_default`) VALUES 
('Inch','1'),
('Feet','0'),
('Centimeter','0'),
('Millimeter','0'),
('Pixel','0');

/*Table structure for table `color_swatches` */

CREATE TABLE IF NOT EXISTS `color_swatches` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` varchar(50) NOT NULL,
  `hex_code` varchar(15) DEFAULT NULL,
  `file_name` varchar(50) DEFAULT NULL,
  `color_type` int(10) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `color_swatches` (`xe_id`, `attribute_id`, `hex_code`, `file_name`, `color_type`) VALUES (1, 'White', '#ffffff', NULL, 4), (2, 'Black', '#050404', NULL, 5), (3, 'Red', '#ee0000', NULL, 5), (4, 'Green', '#21e842', NULL, 6), (5, 'Blue', '#021ddd', NULL, 5);

/*Table structure for table `color_types` */

CREATE TABLE IF NOT EXISTS `color_types` (
  `xe_id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

/*Data for the table `color_types` */

INSERT INTO `color_types`(`xe_id`, `name`) VALUES (4, 'White'),(5, 'Dark'),(6, 'Light');

/*Table structure for table `languages` */

CREATE TABLE `languages` (
  `xe_id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `file_name` varchar(60) DEFAULT NULL,
  `flag` varchar(60) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `is_enable` tinyint(1) DEFAULT '0',
  `is_default` tinyint(1) DEFAULT '0',
  `store_id` int(4) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `languages` */

INSERT INTO `languages` (`name`, `store_id`, `file_name`, `flag`, `type`, `is_enable`, `is_default`) VALUES
('English', '1', 'lang_english.json', 'english.png', 'tool', 1, 1),
('English', '1', 'lang_english.json', 'english.png', 'admin', 1, 1),
('Dutch', '1', 'lang_dutch.json', 'dutch.png', 'tool', 0, 0),
('German', '1', 'lang_german.json', 'german.png', 'admin', 0, 0),
('French', '1', 'lang_french.json', 'french.png', 'admin', 0, 0),
('Italian', '1', 'lang_italian.json', 'italian.png', 'admin', 0, 0),
('Spanish', '1', 'lang_spanish.json', 'spanish.png', 'admin', 0, 0),
('Japanese', '1', 'lang_japanese.json', 'japanese.png', 'admin', 0, 0),
('Chinese', '1', 'lang_chinese.json', 'chinese.png', 'admin', 0, 0),
('Dutch', '1', 'lang_dutch.json', 'dutch.png', 'admin', 0, 0),
('Greek', '1', 'lang_greek.json', 'greek.png', 'admin', 0, 0),
('Hebrew', '1', 'lang_hebrew.json', 'hebrew.png', 'admin', 0, 0),
('Polish', '1', 'lang_polish.json', 'polish.png', 'admin', 0, 0),
('Portuguese', '1', 'lang_portuguese.json', 'portuguese.png', 'admin', 0, 0),
('Norwegian', '1', 'lang_norwegian.json', 'norwegian.png', 'admin', 0, 0),
('French', '1', 'lang_french.json', 'french.png', 'tool', 0, 0),
('German', '1', 'lang_german.json', 'german.png', 'tool', 0, 0),
('Italian', '1', 'lang_italian.json', 'italian.png', 'tool', 0, 0),
('Japanese', '1', 'lang_japanese.json', 'japanese.png', 'tool', 0, 0),
('Polish', '1', 'lang_polish.json', 'polish.png', 'tool', 0, 0),
('Portuguese', '1', 'lang_portuguese.json', 'portuguese.png', 'tool', 0, 0),
('Spanish', '1', 'lang_spanish.json', 'spanish.png', 'tool', 0, 0),
('Greek', '1', 'lang_greek.json', 'greek.png', 'tool', 0, 0),
('Arabic', '1', 'lang_arabic.json', 'arabic.png', 'tool', 0, 0),
('Chinese', '1', 'lang_chinese.json', 'chinese.png', 'tool', 0, 0),
('Norwegian', '1', 'lang_norwegian.json', 'norwegian.png', 'tool', 0, 0),
('Hebrew', '1', 'lang_hebrew.json', 'hebrew.png', 'tool', 0, 0);

/*Table structure for table `settings` */

CREATE TABLE IF NOT EXISTS `settings` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(60) DEFAULT NULL,
  `setting_value` text,
  `type` tinyint(1) DEFAULT '0',
  `store_id` int(4) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` (`xe_id`, `setting_key`, `setting_value`, `type`, `store_id`, `updated_at`) VALUES
  (1, 'revision_no', '1', 0, 1, '2020-03-30 17:00:58'),
  (40, 'color', 'color', 4, 1, '2020-03-30 17:00:58'),
  (41, 'size', 'size', 4, 1, '2020-03-30 17:00:58'),
  (58, 'direct_check_out', '1', 5, 1, '2020-03-31 11:02:55'),
  (59, 'cart_terms_condition', '{"is_enabled":true,"description":"Please make sure you are uploading high resolution images when customizing your product.  If your artwork does not follow our recommended guidelines, we cannot guarantee the quality of the final printed product. Please make sure to check your design for SPELLING, CLARITY OF THE IMAGES and TEXT PLACEMENT. What you see on the screen is what we will be printing on the product. We are not responsible for the customers supplied artwork, spelling or wrong placement of the text."}', 5, 1, '2020-03-31 11:02:55'),
  (60, 'order_notes', '{"is_enabled":true,"description":"Notes are the first thing we check when we start work on your order. Tell us if you have any question on the printing process, quality or any customization."}', 5, 1, '2020-03-31 11:02:55'),
  (70, 'measurement_unit', '{"unit":1,"display_lebel":"Inch"}', 1, 1, '2020-03-31 11:18:17'),
  (71, 'currency', '{"currencyId":1,"currency":"$","separator":".","post_fix":"USD"}', 1, 1, '2020-03-31 11:18:17'),
  (72, 'email', 'admin@gmail.com', 1, 1, '2020-03-31 11:18:17'),
  (73, 'advance_settings', '{"prompt_close_window":true,"price_segregation":true,"progress_wizard":true,"social_share":true,"order_artwork_status":true,"maximum_gallery_size": 200}', 1, 1, '2020-03-31 11:18:17'),
  (74, 'facebook_import', '{"app_id":"","domain_name":"","url":""}', 3, 1, '2020-03-31 11:18:54'),
  (75, 'dropbox_import', '{"is_enabled":true}', 3, 1, '2020-03-31 11:18:54'),
  (76, 'google_drive_import', '{"is_enabled":true}', 3, 1, '2020-03-31 11:18:54'),
  (77, 'file_uploaded', '{"width":"200","height":"200","low_resolution":true,"max_file":"10","upload_tip":true,"tip_message":"On uploading one or several images, you agree to terms on using these images. Make use of third party images or infringing somebody else\'s rights in unlawful."}', 3, 1, '2020-03-31 11:18:54'),
  (78, 'terms_condition', '{"is_enabled":true,"message":"By uploading an Image you guarantee that any people which are clearly identifiable have consented to have their likeness printed or displayed, or that you have full rights to use the Image in this manner and accept full responsibility for such use.","is_default_status":true}', 3, 1, '2020-03-31 11:18:54'),
  (91, 'theme_color', '#5667d6', 2, 1, '2020-03-31 11:26:54'),
  (92, 'custom_css', 'style.css', 2, 1, '2020-03-31 11:26:54'),
  (93, 'theme_layouts', '1', 2, 1, '2020-03-31 11:26:54');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

/*Table structure for table `stores` */

CREATE TABLE IF NOT EXISTS `stores` (
  `xe_id` int(4) NOT NULL AUTO_INCREMENT,
  `store_name` varchar(60) DEFAULT NULL,
  `store_url` varchar(60) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(1) DEFAULT '1',
  `settings` text,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `layouts` */

CREATE TABLE IF NOT EXISTS `layouts` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `layouts` */

INSERT INTO `layouts`(name,file_name) VALUES 
('Theme 1','theme-1.svg'),
('Theme 2','theme-2.svg'),
('Theme 3','theme-3.svg');
