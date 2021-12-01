-- Add new columns in print_profile_engrave_settings table

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = 'print_profile_engrave_settings'
		AND table_schema = DATABASE()
        AND column_name IN('is_BWGray_enabled', 'is_black_white')
    ) > 0,
    "SELECT 1",
  "ALTER TABLE `print_profile_engrave_settings` ADD `is_BWGray_enabled` TINYINT(1) NULL DEFAULT '0' AFTER `is_engrave_preview_image`, ADD `is_black_white` TINYINT(1) NULL DEFAULT '0' AFTER `is_BWGray_enabled`, ADD `is_gary_scale` TINYINT(1) NULL DEFAULT '0' AFTER `is_black_white`;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- Changed column data type to varchar from enum

ALTER TABLE `price_advanced_price_settings` CHANGE `area_calculation_type` `area_calculation_type` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'design_area, bound_area, print_area';