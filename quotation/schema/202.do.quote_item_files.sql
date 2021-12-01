/* Add new column decoration_settings_id in quote_item_files table */

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'quote_item_files'
        AND table_schema = DATABASE()
        AND column_name IN('decoration_settings_id')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE quote_item_files ADD decoration_settings_id INT(11) NOT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

/*Data for the table `production_template_abbriviations` */

INSERT INTO `production_template_abbriviations` (`abbr_name`,`module_id`) VALUES
  ('{quote_total_amount}','1');