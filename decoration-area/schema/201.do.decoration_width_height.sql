-- Change 'width' column type in  print_areas table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'print_areas'
        AND table_schema = DATABASE()
        AND column_name IN('width')
    ) < 0,
    "SELECT 1",
	"ALTER TABLE `print_areas` CHANGE `width` `width` VARCHAR(30) DEFAULT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Change 'height' column type in in  print_areas table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'print_areas'
        AND table_schema = DATABASE()
        AND column_name IN('height')
    ) < 0,
    "SELECT 1",
	"ALTER TABLE `print_areas` CHANGE `height` `height` VARCHAR(30) DEFAULT NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;