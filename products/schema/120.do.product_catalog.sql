/*Table structure for table `catalog_product_rel` */

CREATE TABLE IF NOT EXISTS `catalog_product_rel` (
  `product_id` 	varchar(50) NOT NULL,
  `catalog_product_id` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;