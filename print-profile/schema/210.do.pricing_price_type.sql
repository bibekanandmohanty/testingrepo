-- Alter print_profile_pricings table setup_price column type

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME = 'print_profile_pricings'
        ) > 0,
        "ALTER TABLE print_profile_pricings CHANGE setup_price setup_price DECIMAL(10,2) NULL;",
        "SELECT 1"
    )
);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Alter price_default_settings table price_value column type

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME = 'price_default_settings'
        ) > 0,
        "ALTER TABLE price_default_settings CHANGE price_value price_value DECIMAL(10,2) NULL;",
        "SELECT 1"
    )
);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Alter price_advanced_price_settings table min_price column type

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME = 'price_advanced_price_settings'
        ) > 0,
        "ALTER TABLE price_advanced_price_settings CHANGE min_price min_price DECIMAL(10,2) NULL;",
        "SELECT 1"
    )
);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Alter price_tier_whitebases table price column type

SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME = 'price_tier_whitebases'
        ) > 0,
        "ALTER TABLE price_tier_whitebases CHANGE price price DECIMAL(10,2) NULL;",
        "SELECT 1"
    )
);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;