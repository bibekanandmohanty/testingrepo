-- Update column for module_name in user_privileges table

UPDATE `user_privileges` SET `module_name`='Production Hub' WHERE `xe_id`='5';

UPDATE `user_privileges` SET `module_name`='Print method' WHERE `xe_id`='3';