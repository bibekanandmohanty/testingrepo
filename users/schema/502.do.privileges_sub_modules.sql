-- Delete a row in privileges_sub_modules table

DELETE FROM privileges_sub_modules WHERE slug = 'general' AND user_privilege_id = '4';

-- Add new row in privileges_sub_modules table

INSERT INTO `privileges_sub_modules` (`user_privilege_id`, `type`, `slug`)
SELECT '4', 'General', 'general'
FROM DUAL
WHERE NOT EXISTS (SELECT user_privilege_id, type, slug FROM privileges_sub_modules 
where user_privilege_id='4' and type='General' and slug='general');