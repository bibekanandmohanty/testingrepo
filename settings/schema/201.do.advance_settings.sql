/* Update Settings value for advance settings in settings table */

UPDATE settings SET setting_value = '{"prompt_close_window":true,"price_segregation":true,"progress_wizard":true,"social_share":false,"order_artwork_status":true,"maximum_gallery_size": 200}' WHERE setting_key = 'advance_settings';