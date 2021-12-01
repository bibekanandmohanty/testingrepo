/* Insert a new row in languages table */

INSERT INTO languages (`name`, `store_id`, `file_name`, `flag`, `type`, `is_enable`, `is_default`)
SELECT 'Finnish', '1', 'lang_finnish.json', 'finnish.png', 'tool', 0, 0
FROM DUAL
WHERE NOT EXISTS (SELECT name FROM languages where name='Finnish' and file_name='lang_finnish.json' and type='tool');

/* Insert a new row in languages table */

INSERT INTO languages (`name`, `store_id`, `file_name`, `flag`, `type`, `is_enable`, `is_default`)
SELECT 'Finnish', '1', 'lang_finnish.json', 'finnish.png', 'admin', 0, 0
FROM DUAL
WHERE NOT EXISTS (SELECT name FROM languages where name='Finnish' and file_name='lang_finnish.json' and type='admin');