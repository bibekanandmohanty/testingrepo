-- Data for the table `production_email_templates` 

INSERT INTO production_email_templates (store_id, module_id, template_type_name, subject, message, is_configured)
SELECT '1','3','send_po', 'Purchase Order', '<span>Hello {contact_name},</span><br><br><span>A purchase odrder ID#: {order_id} is created. Please check the attached PDF for the purchase order details.</span><br><br><span>Thanks</span>', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT store_id, module_id, template_type_name FROM production_email_templates 
where store_id='1' and module_id='3' and template_type_name='send_po');

-- Data for the table `production_template_abbriviations` 

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{po_id}','3'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{po_id}' and module_id='3');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{contact_name}','3'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{contact_name}' and module_id='3');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{vendor_name}','3'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{vendor_name}' and module_id='3');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{vendor_email}','3'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{vendor_email}' and module_id='3');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{created_date}','3'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{created_date}' and module_id='3');

INSERT INTO production_template_abbriviations (abbr_name, module_id)
SELECT '{expected_date_of_delivery}','3'
FROM DUAL
WHERE NOT EXISTS (SELECT abbr_name, module_id FROM production_template_abbriviations 
where abbr_name='{expected_date_of_delivery}' and module_id='3');

