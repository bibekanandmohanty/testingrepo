/* Update Settings value for dropbox_import settings in settings table */

UPDATE settings SET setting_value = '{"is_enabled":false}' WHERE setting_key = 'dropbox_import';

/* Update Settings value for google_drive_import settings in settings table */

UPDATE settings SET setting_value = '{"is_enabled":false}' WHERE setting_key = 'google_drive_import';