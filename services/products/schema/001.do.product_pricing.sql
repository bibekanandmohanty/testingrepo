/*Table structure for table `attribute_price_rules` */

CREATE TABLE IF NOT EXISTS `attribute_price_rules` (
  `product_id` varchar(50) DEFAULT NULL,
  `attribute_id` varchar(50) DEFAULT NULL,
  `attribute_term_id` varchar(50) DEFAULT NULL,
  `print_profile_id` int(11) DEFAULT NULL,
  `price` decimal(6,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
