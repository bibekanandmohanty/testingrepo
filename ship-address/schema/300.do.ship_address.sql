/*Table structure for table ship_to_address*/

CREATE TABLE IF NOT EXISTS ship_to_address (
  xe_id int(7) NOT NULL AUTO_INCREMENT,
  name varchar(40) NOT NULL,
  email varchar(50) NOT NULL,
  phone varchar(20) DEFAULT NULL,
  company_name varchar(30) DEFAULT NULL,
  country_code varchar(12) DEFAULT NULL,
  state_code varchar(20) DEFAULT NULL,
  city varchar(50) DEFAULT NULL,
  zip_code varchar(12) DEFAULT NULL,
  ship_address text,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;