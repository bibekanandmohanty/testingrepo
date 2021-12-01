
-- Insert public_url as order email template abbriviation in production_template_abbriviations 

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{public_url}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{public_url}' AND module_id=2);