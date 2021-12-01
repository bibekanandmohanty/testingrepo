/* Add new column label in app_units table */

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'app_units'
        AND table_schema = DATABASE()
        AND column_name IN('label')
    ) > 0,
    "SELECT 1",
	"ALTER TABLE `app_units` ADD COLUMN `label` VARCHAR(20) NULL;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

/* Add label values in app_units table */

UPDATE `app_units` SET `label`='in' WHERE `name`='Inch';
UPDATE `app_units` SET `label`='ft' WHERE `name`='Feet';
UPDATE `app_units` SET `label`='cm' WHERE `name`='Centimeter';
UPDATE `app_units` SET `label`='mm' WHERE `name`='Millimeter';
UPDATE `app_units` SET `label`='px' WHERE `name`='Pixel';

/* Insert a new row in app_units table */

INSERT INTO app_units (name, is_default, label)
SELECT 'Meter', '0', 'm'
FROM DUAL
WHERE NOT EXISTS (SELECT name FROM app_units where name='Meter' and label='m');
