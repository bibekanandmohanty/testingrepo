
-- Insert Customer as row-6 in production_hub_modules

INSERT INTO  production_hub_modules (xe_id, name)  
SELECT '6', 'Customer' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_hub_modules WHERE xe_id=6);

-- Insert order_id as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{order_id}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{order_id}' AND module_id=2);

-- Insert order_status as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{order_status}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{order_status}' AND module_id=2);

-- Insert artwork_status as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{artwork_status}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{artwork_status}' AND module_id=2);

-- Insert order_created_date as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{order_created_date}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{order_created_date}' AND module_id=2);

-- Insert order_value as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{order_value}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{order_value}' AND module_id=2);

-- Insert payment_type as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{payment_type}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{payment_type}' AND module_id=2);

-- Insert order_notes as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{order_notes}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{order_notes}' AND module_id=2);

-- Insert product_name as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{product_name}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{product_name}' AND module_id=2);

-- Insert customer_name as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{customer_name}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{customer_name}' AND module_id=2);

-- Insert customer_address as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{customer_address}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{customer_address}' AND module_id=2);

-- Insert mobile_no as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{mobile_no}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{mobile_no}' AND module_id=2);

-- Insert customer_email as order email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{customer_email}', '2' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{customer_email}' AND module_id=2);


-- Insert customer_name as customer email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{customer_name}', '6' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{customer_name}' AND module_id=6);


-- Insert customer_address as customer email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{customer_address}', '6' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{customer_address}' AND module_id=6);

-- Insert customer_email as customer email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{customer_email}', '6' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{customer_email}' AND module_id=6);

-- Insert signup_date as customer email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{signup_date}', '6' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{signup_date}' AND module_id=6);

-- Insert order_value as customer email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{order_value}', '6' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{order_value}' AND module_id=6);

-- Insert number_of_orders as customer email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{number_of_orders}', '6' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{number_of_orders}' AND module_id=6);

-- Insert last_order as customer email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{last_order}', '6' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{last_order}' AND module_id=6);

-- Insert mobile_no as customer email template abbriviation in production_template_abbriviations

INSERT INTO  production_template_abbriviations (abbr_name, module_id)  
SELECT '{mobile_no}', '6' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_template_abbriviations WHERE abbr_name='{mobile_no}' AND module_id=6);

-- Insert order_email_send as order email template abbriviation in production_email_templates

INSERT INTO  production_email_templates (store_id,module_id,template_type_name,subject,message,is_configured)  
SELECT '1', '2' , 'order_email_send' , 'Artwork files for Order {order_id}  {customer_name}' , '<div><div>Hello</div></div><div>Please find the details of the customer below.<br>Customer name {customer_name}</div><div>Customer email  {customer_email}</div><div>Customer address {customer_address}<br>Order Status  {order_status}</div><div>Order Created Date {order_created_date}</div><div>Order Artwork Status {artwork_status}</div><div>Order Total  {order_value}</div><div>Payment Type  {payment_type}</div><div>Order Notes {order_notes}</div><div>Product Name {product_name}</div><div>Customer Mobile No {mobile_no}</div><div>Also find the artwork files for this order {order_id} in the attachment.</div><div><br></div><div><br></div><div><b>Thanks</b><br><b><i>ImprintNext</i></b></div>' , '1' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_email_templates WHERE template_type_name='order_email_send' AND module_id=2);

-- Insert order_email_send as order email template abbriviation in production_email_templates

INSERT INTO  production_email_templates (store_id,module_id,template_type_name,subject,message,is_configured)  
SELECT '1', '6' , 'promotional_email' , 'Hi {customer_name}' , '<div>Hello {customer_name}</div><div><br></div><div><span>Please find the below details</span><span>.</span><br></div><div>Customer email {customer_email}<span><br></span></div><div>Your Last Order {last_order}</div><div>Address {customer_address}</div><div>Register Date {signup_date}</div><div><br></div><div><br></div><div><b>Thanks</b><br><i><b>ImprintNext</b></i><br></div>' , '1' FROM DUAL WHERE NOT EXISTS (SELECT * FROM production_email_templates WHERE template_type_name='promotional_email' AND module_id=6);
