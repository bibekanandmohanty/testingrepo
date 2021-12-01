-- Insert parent category relations in the print_profile_assets_category_rel table

INSERT INTO `print_profile_assets_category_rel` (`print_profile_id`, `asset_type_id`, `category_id`)
SELECT 1, 3, 1
FROM DUAL
WHERE NOT EXISTS (SELECT `print_profile_id` FROM `print_profile_assets_category_rel` where `print_profile_id`=1 and `asset_type_id`=3 and `category_id`=1);

INSERT INTO `print_profile_assets_category_rel` (`print_profile_id`, `asset_type_id`, `category_id`)
SELECT 1, 3, 2
FROM DUAL
WHERE NOT EXISTS (SELECT `print_profile_id` FROM `print_profile_assets_category_rel` where `print_profile_id`=1 and `asset_type_id`=3 and `category_id`=2);

INSERT INTO `print_profile_assets_category_rel` (`print_profile_id`, `asset_type_id`, `category_id`)
SELECT 1, 3, 3
FROM DUAL
WHERE NOT EXISTS (SELECT `print_profile_id` FROM `print_profile_assets_category_rel` where `print_profile_id`=1 and `asset_type_id`=3 and `category_id`=3);

INSERT INTO `print_profile_assets_category_rel` (`print_profile_id`, `asset_type_id`, `category_id`)
SELECT 1, 3, 4
FROM DUAL
WHERE NOT EXISTS (SELECT `print_profile_id` FROM `print_profile_assets_category_rel` where `print_profile_id`=1 and `asset_type_id`=3 and `category_id`=4);

INSERT INTO `print_profile_assets_category_rel` (`print_profile_id`, `asset_type_id`, `category_id`)
SELECT 1, 3, 5
FROM DUAL
WHERE NOT EXISTS (SELECT `print_profile_id` FROM `print_profile_assets_category_rel` where `print_profile_id`=1 and `asset_type_id`=3 and `category_id`=5);

INSERT INTO `print_profile_assets_category_rel` (`print_profile_id`, `asset_type_id`, `category_id`)
SELECT 2, 3, 1
FROM DUAL
WHERE NOT EXISTS (SELECT `print_profile_id` FROM `print_profile_assets_category_rel` where `print_profile_id`=2 and `asset_type_id`=3 and `category_id`=1);

INSERT INTO `print_profile_assets_category_rel` (`print_profile_id`, `asset_type_id`, `category_id`)
SELECT 2, 3, 2
FROM DUAL
WHERE NOT EXISTS (SELECT `print_profile_id` FROM `print_profile_assets_category_rel` where `print_profile_id`=2 and `asset_type_id`=3 and `category_id`=2);

INSERT INTO `print_profile_assets_category_rel` (`print_profile_id`, `asset_type_id`, `category_id`)
SELECT 2, 3, 3
FROM DUAL
WHERE NOT EXISTS (SELECT `print_profile_id` FROM `print_profile_assets_category_rel` where `print_profile_id`=2 and `asset_type_id`=3 and `category_id`=3);

INSERT INTO `print_profile_assets_category_rel` (`print_profile_id`, `asset_type_id`, `category_id`)
SELECT 2, 3, 4
FROM DUAL
WHERE NOT EXISTS (SELECT `print_profile_id` FROM `print_profile_assets_category_rel` where `print_profile_id`=2 and `asset_type_id`=3 and `category_id`=4);

INSERT INTO `print_profile_assets_category_rel` (`print_profile_id`, `asset_type_id`, `category_id`)
SELECT 2, 3, 5
FROM DUAL
WHERE NOT EXISTS (SELECT `print_profile_id` FROM `print_profile_assets_category_rel` where `print_profile_id`=2 and `asset_type_id`=3 and `category_id`=5);
