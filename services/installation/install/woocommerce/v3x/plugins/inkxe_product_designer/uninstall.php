<?php 

/**
 * product designer plugin uninstall
 *
 *
 */

	if (!defined('WP_UNINSTALL_PLUGIN')) {
	    die;
	}
	global $wpdb;
	$table_name = $wpdb->prefix . 'multipleshippingaddress';
	$sql = "DROP TABLE IF EXISTS $table_name";
	$wpdb->query($sql);
?>