
/* Insert a new row in features table */

INSERT INTO features (`asset_type_id`, `name`, `slug`) 
SELECT 0, 'QR Code', 'qr' 
FROM DUAL 
WHERE NOT EXISTS (SELECT NAME FROM features WHERE `name`='QR Code' AND `slug`='qr');