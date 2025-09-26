<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('woocommerce_cmi_gateway_settings');

// Clean up any plugin-specific database tables or data
global $wpdb;

// Delete all metadata related to CMI transactions
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_cmi_%'");

// Optional: Remove any scheduled cron jobs
wp_clear_scheduled_hook('wc_cmi_gateway_cron');
