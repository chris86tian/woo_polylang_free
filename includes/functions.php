<?php
/**
 * Helper functions
 */

if (!defined('ABSPATH')) {
    exit;
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
 * Log debug messages
 */
function wc_polylang_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $logger = wc_get_logger();
        $logger->log($level, $message, array('source' => 'wc-polylang-integration'));
    }
}

/**
 * Get translated WooCommerce page URL
 */
function wc_polylang_get_page_url($page, $language = null) {
    if (!function_exists('pll_get_post')) {
        return false;
    }
    
    $language = $language ?: pll_current_language();
    
    switch ($page) {
        case 'shop':
            $page_id = wc_get_page_id('shop');
            break;
        case 'cart':
            $page_id = wc_get_page_id('cart');
            break;
        case 'checkout':
            $page_id = wc_get_page_id('checkout');
            break;
        case 'myaccount':
            $page_id = wc_get_page_id('myaccount');
            break;
        default:
            return false;
    }
    
    $translated_page_id = pll_get_post($page_id, $language);
    
    return $translated_page_id ? get_permalink($translated_page_id) : false;
}

/**
 * Get product in specific language
 */
function wc_polylang_get_product($product_id, $language = null) {
    if (!function_exists('pll_get_post')) {
        return wc_get_product($product_id);
    }
    
    $language = $language ?: pll_current_language();
    $translated_id = pll_get_post($product_id, $language);
    
    return $translated_id ? wc_get_product($translated_id) : wc_get_product($product_id);
}

/**
 * Get category in specific language
 */
function wc_polylang_get_category($category_id, $language = null) {
    if (!function_exists('pll_get_term')) {
        return get_term($category_id, 'product_cat');
    }
    
    $language = $language ?: pll_current_language();
    $translated_id = pll_get_term($category_id, $language);
    
    return $translated_id ? get_term($translated_id, 'product_cat') : get_term($category_id, 'product_cat');
}

/**
 * Check if current page is a WooCommerce page
 */
function wc_polylang_is_woocommerce_page() {
    return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
}

/**
 * Get language switcher for WooCommerce pages
 */
function wc_polylang_language_switcher($args = array()) {
    if (!function_exists('pll_the_languages')) {
        return '';
    }
    
    $defaults = array(
        'dropdown' => 0,
        'show_names' => 1,
        'show_flags' => 0,
        'hide_if_empty' => 1,
        'force_home' => 0,
        'echo' => 1,
        'hide_if_no_translation' => 0,
        'hide_current' => 0,
        'post_id' => null,
        'raw' => 0
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Handle WooCommerce pages
    if (wc_polylang_is_woocommerce_page() && !$args['post_id']) {
        global $post;
        if ($post && is_singular('product')) {
            $args['post_id'] = $post->ID;
        }
    }
    
    return pll_the_languages($args);
}

/**
 * Get current order language
 */
function wc_polylang_get_order_language($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return false;
    }
    
    // Get language from order meta
    $language = $order->get_meta('_order_language');
    
    if (!$language && function_exists('pll_get_post_language')) {
        // Fallback: get language from first product
        $items = $order->get_items();
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            if ($product_id) {
                $language = pll_get_post_language($product_id);
                if ($language) {
                    // Save for future use
                    $order->update_meta_data('_order_language', $language);
                    $order->save();
                    break;
                }
            }
        }
    }
    
    return $language ?: (function_exists('pll_default_language') ? pll_default_language() : 'de');
}

/**
 * Set order language
 */
function wc_polylang_set_order_language($order_id, $language = null) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return false;
    }
    
    $language = $language ?: (function_exists('pll_current_language') ? pll_current_language() : 'de');
    
    $order->update_meta_data('_order_language', $language);
    $order->save();
    
    return true;
}

/**
 * Get multilingual breadcrumbs
 */
function wc_polylang_get_breadcrumbs($args = array()) {
    if (!function_exists('woocommerce_breadcrumb')) {
        return '';
    }
    
    $defaults = array(
        'delimiter' => '&nbsp;&#47;&nbsp;',
        'wrap_before' => '<nav class="woocommerce-breadcrumb">',
        'wrap_after' => '</nav>',
        'before' => '',
        'after' => '',
        'home' => _x('Home', 'breadcrumb', 'woocommerce'),
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Translate home text
    if (function_exists('pll__')) {
        $args['home'] = pll__($args['home']) ?: $args['home'];
    }
    
    ob_start();
    woocommerce_breadcrumb($args);
    return ob_get_clean();
}

/**
 * Get plugin settings
 */
function wc_polylang_get_settings() {
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
    foreach ($settings as $key => $value) {
        update_option('wc_polylang_' . $key, $value);
    }
}

/**
 * Get translation statistics
 */
function wc_polylang_get_translation_stats() {
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
    
    return $stats;
}

/**
 * Clear translation cache
 */
function wc_polylang_clear_cache() {
    // Clear WordPress cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Clear object cache
    if (function_exists('wp_cache_flush_group')) {
        wp_cache_flush_group('wc_polylang');
    }
    
    // Clear Polylang cache
    if (function_exists('pll_cache_flush')) {
        pll_cache_flush();
    }
    
    // Clear WooCommerce cache
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients();
    }
    
    do_action('wc_polylang_cache_cleared');
}
