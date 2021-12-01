/*Table structure for table user_store_rel*/

CREATE TABLE IF NOT EXISTS user_store_rel (
  user_id int(7) NOT NULL,
  store_id int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


/*Add 'store_id' column in vendor table*/

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'vendor'
        AND table_schema = DATABASE()
        AND column_name IN('store_id')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE vendor ADD store_id INT(4) NOT NULL DEFAULT 1;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;


/*Add 'store_id' column in ship_to_address table*/

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'ship_to_address'
        AND table_schema = DATABASE()
        AND column_name IN('store_id')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE ship_to_address ADD store_id INT(4) NOT NULL DEFAULT 1;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

-- Add new column is_active in  stores table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'stores'
        AND table_schema = DATABASE()
        AND column_name IN('is_active')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE `stores` ADD `is_active` tinyint(1) DEFAULT 1;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

/*Add 'store_id' column in app_units table*/

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'app_units'
        AND table_schema = DATABASE()
        AND column_name IN('store_id')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE app_units ADD store_id INT(4) NOT NULL DEFAULT 1;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

/*Add 'store_id' column in quote_dynamic_form_values table*/

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_dynamic_form_values'
        AND table_schema = DATABASE()
        AND column_name IN('store_id')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_dynamic_form_values ADD store_id INT(4) NOT NULL DEFAULT 1;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;