<?php
/**
 * Helper functions - MIT DEBUG
 */

if (!defined('ABSPATH')) {
    exit;
}

// Debug-Funktion für functions.php
function wc_polylang_debug_log($message, $level = 'INFO') {
    if (class_exists('WC_Polylang_Debug')) {
        WC_Polylang_Debug::log("FUNCTIONS.PHP: " . $message, $level);
    }
}

wc_polylang_debug_log("functions.php wird geladen...");

/**
 * Check if WooCommerce Polylang Integration is active
 */
function is_wc_polylang_integration_active() {
    wc_polylang_debug_log("is_wc_polylang_integration_active() aufgerufen");
    return class_exists('WC_Polylang_Integration');
}

/**
 * Get plugin version
 */
function wc_polylang_integration_version() {
    wc_polylang_debug_log("wc_polylang_integration_version() aufgerufen");
    return WC_POLYLANG_INTEGRATION_VERSION;
}

/**
 * Log debug messages
 */
function wc_polylang_log($message, $level = 'info') {
    wc_polylang_debug_log("wc_polylang_log() aufgerufen: " . $message);
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $logger = wc_get_logger();
        $logger->log($level, $message, array('source' => 'wc-polylang-integration'));
    }
}

/**
 * Get plugin settings
 */
function wc_polylang_get_settings() {
    wc_polylang_debug_log("wc_polylang_get_settings() aufgerufen");
    return array(
        'enable_product_translation' => get_option('wc_polylang_enable_product_translation', 'yes'),
        'enable_category_translation' => get_option('wc_polylang_enable_category_translation', 'yes'),
        'enable_widget_translation' => get_option('wc_polylang_enable_widget_translation', 'yes'),
        'enable_email_translation' => get_option('wc_polylang_enable_email_translation', 'yes'),
        'enable_seo_translation' => get_option('wc_polylang_enable_seo_translation', 'yes'),
        'enable_custom_fields_translation' => get_option('wc_polylang_enable_custom_fields_translation', 'yes'),
        'default_language' => get_option('wc_polylang_default_language', 'de'),
        'seo_canonical_urls' => get_option('wc_polylang_seo_canonical_urls', 'yes'),
        'seo_hreflang_tags' => get_option('wc_polylang_seo_hreflang_tags', 'yes'),
    );
}

/**
 * Update plugin settings
 */
function wc_polylang_update_settings($settings) {
    wc_polylang_debug_log("wc_polylang_update_settings() aufgerufen");
    foreach ($settings as $key => $value) {
        update_option('wc_polylang_' . $key, $value);
    }
}

/**
 * Get translation statistics
 */
function wc_polylang_get_translation_stats() {
    wc_polylang_debug_log("wc_polylang_get_translation_stats() aufgerufen");
    $stats = array(
        'products' => array(
            'total' => 0,
            'translated' => 0,
            'percentage' => 0
        ),
        'categories' => array(
            'total' => 0,
            'translated' => 0,
            'percentage' => 0
        )
    );
    
    if (!function_exists('pll_get_post_translations')) {
        wc_polylang_debug_log("pll_get_post_translations() nicht verfügbar");
        return $stats;
    }
    
    // Count products
    $products = get_posts(array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids'
    ));
    
    $stats['products']['total'] = count($products);
    
    foreach ($products as $product_id) {
        $translations = pll_get_post_translations($product_id);
        if (count($translations) > 1) {
            $stats['products']['translated']++;
        }
    }
    
    if ($stats['products']['total'] > 0) {
        $stats['products']['percentage'] = round(($stats['products']['translated'] / $stats['products']['total']) * 100);
    }
    
    // Count categories
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false
    ));
    
    $stats['categories']['total'] = count($categories);
    
    if (function_exists('pll_get_term_translations')) {
        foreach ($categories as $category) {
            $translations = pll_get_term_translations($category->term_id);
            if (count($translations) > 1) {
                $stats['categories']['translated']++;
            }
        }
    }
    
    if ($stats['categories']['total'] > 0) {
        $stats['categories']['percentage'] = round(($stats['categories']['translated'] / $stats['categories']['total']) * 100);
    }
    
    wc_polylang_debug_log("Translation stats berechnet: " . json_encode($stats));
    return $stats;
}

wc_polylang_debug_log("functions.php erfolgreich geladen");
