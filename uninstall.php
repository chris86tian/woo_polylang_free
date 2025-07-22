<?php
/**
 * Uninstall script for WooCommerce Polylang Integration
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user has permission to delete plugins
if (!current_user_can('activate_plugins')) {
    return;
}

// Delete plugin options
$options_to_delete = array(
    'wc_polylang_enable_product_translation',
    'wc_polylang_enable_category_translation',
    'wc_polylang_enable_widget_translation',
    'wc_polylang_enable_email_translation',
    'wc_polylang_enable_seo_translation',
    'wc_polylang_enable_custom_fields_translation',
    'wc_polylang_default_language',
    'wc_polylang_seo_canonical_urls',
    'wc_polylang_seo_hreflang_tags',
);

foreach ($options_to_delete as $option) {
    delete_option($option);
    delete_site_option($option); // For multisite
}

// Drop custom database tables
global $wpdb;

$table_name = $wpdb->prefix . 'wc_polylang_translations';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Clear any cached data
wp_cache_flush();

// Remove any scheduled events
wp_clear_scheduled_hook('wc_polylang_cleanup');

// Delete transients
delete_transient('wc_polylang_stats');
delete_transient('wc_polylang_languages');

// Remove user meta related to plugin
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wc_polylang_%'");

// Clean up any remaining plugin data
do_action('wc_polylang_uninstall_cleanup');
