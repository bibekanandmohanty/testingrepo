/*Table structure for table `user_designs` */

CREATE TABLE IF NOT EXISTS user_designs (
  xe_id int(10) NOT NULL AUTO_INCREMENT,
  customer_id varchar(50) DEFAULT NULL,
  design_id int(11) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (xe_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
