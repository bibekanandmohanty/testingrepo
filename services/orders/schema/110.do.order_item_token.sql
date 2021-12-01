/*Table structure for table order_item_token */

CREATE TABLE IF NOT EXISTS order_item_token (
  order_id varchar(50) NOT NULL,
  order_item_id varchar(50) NOT NULL,
  token text 
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;