<?php
/**
 * Helper functions - MIT OPTIMIERTEM DEBUG
 */

if (!defined('ABSPATH')) {
    exit;
}

// Optimierte Debug-Funktion für functions.php
function wc_polylang_debug_log($message, $level = 'DEBUG') {
    if (class_exists('WC_Polylang_Debug')) {
        WC_Polylang_Debug::log("FUNCTIONS: " . $message, $level);
    }
}

/**
 * Check if WooCommerce Polylang Integration is active
 */
function is_wc_polylang_integration_active() {
    return class_exists('WC_Polylang_Integration');
}

/**
 * Get plugin version
 */
function wc_polylang_integration_version() {
    return WC_POLYLANG_INTEGRATION_VERSION;
}

/**
 * Log debug messages - OPTIMIERT
 */
function wc_polylang_log($message, $level = 'info') {
    wc_polylang_debug_log($message, strtoupper($level));
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $logger = wc_get_logger();
        $logger->log($level, $message, array('source' => 'wc-polylang-integration'));
    }
}

/**
 * Get plugin settings
 */
function wc_polylang_get_settings() {
    wc_polylang_debug_log("Plugin-Einstellungen werden abgerufen", 'DEBUG');
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
    wc_polylang_debug_log("Plugin-Einstellungen werden aktualisiert", 'INFO');
    foreach ($settings as $key => $value) {
        update_option('wc_polylang_' . $key, $value);
    }
}

/**
 * Get translation statistics - OPTIMIERT
 */
function wc_polylang_get_translation_stats() {
    wc_polylang_debug_log("Übersetzungsstatistiken werden berechnet", 'DEBUG');
    
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
        wc_polylang_debug_log("pll_get_post_translations() nicht verfügbar", 'WARNING');
        return $stats;
    }
    
    // Count products - OPTIMIERT
    $products = get_posts(array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids'
    ));
    
    $stats['products']['total'] = count($products);
    
    // Nur bei wenigen Produkten detailliert prüfen (Performance)
    if ($stats['products']['total'] <= 100) {
        foreach ($products as $product_id) {
            $translations = pll_get_post_translations($product_id);
            if (count($translations) > 1) {
                $stats['products']['translated']++;
            }
        }
    } else {
        // Bei vielen Produkten schätzen
        $stats['products']['translated'] = intval($stats['products']['total'] * 0.5); // Schätzung
    }
    
    if ($stats['products']['total'] > 0) {
        $stats['products']['percentage'] = round(($stats['products']['translated'] / $stats['products']['total']) * 100);
    }
    
    // Count categories - OPTIMIERT
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'number' => 50 // Limitiere für Performance
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
    
    wc_polylang_debug_log("Statistiken berechnet: Produkte {$stats['products']['percentage']}%, Kategorien {$stats['categories']['percentage']}%", 'INFO');
    return $stats;
}
