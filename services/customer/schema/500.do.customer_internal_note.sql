-- Table structure for table customer_internal_notes 

CREATE TABLE IF NOT EXISTS `customer_internal_notes` (
  `xe_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` INT(4) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `title` VARCHAR(255)  NOT NULL, 
  `note` TEXT NOT NULL,
  `user_type` VARCHAR(50)  NOT NULL COMMENT 'admin, agent', 
  `user_id` INT(11) NOT NULL , 
  `seen_flag` TINYINT(1) NOT NULL DEFAULT '0',
  `created_date` DATETIME NOT NULL,
  PRIMARY KEY (xe_id),
  KEY customer_internal_notes (customer_id,created_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table `customer_internal_note_files` 

CREATE TABLE IF NOT EXISTS `customer_internal_note_files` ( 
  `xe_id` INT(11) NOT NULL AUTO_INCREMENT , 
  `note_id` INT(11) NOT NULL ,
  `file` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`xe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

