/* Add new column uv_file in decoration_objects table */

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'decoration_objects'
        AND table_schema = DATABASE()
        AND column_name IN('uv_file')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `decoration_objects` ADD `uv_file` VARCHAR(60) NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;