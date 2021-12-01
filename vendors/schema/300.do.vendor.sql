/*Table structure for table vendor*/

CREATE TABLE IF NOT EXISTS vendor (
  xe_id int(7)  NOT NULL AUTO_INCREMENT,
  company_name varchar(40) NOT NULL,
  contact_name varchar(40) NOT NULL,
  email varchar(50) NOT NULL,
  phone varchar(20) DEFAULT NULL,
  logo varchar(35) DEFAULT NULL,
  country_code varchar(30) DEFAULT NULL,
  state_code varchar(30) DEFAULT NULL,
  city varchar(50) DEFAULT NULL,
  zip_code varchar(30) DEFAULT NULL,
  billing_address text,
  is_live tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Table structure for table vendor_category_rel*/

CREATE TABLE IF NOT EXISTS vendor_category_rel (
  vendor_id int(7) NOT NULL,
  category_id varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;