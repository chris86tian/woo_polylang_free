<?php
/**
 * Hooks and API for third-party plugins
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Hooks {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize hooks
     */
    public function init() {
        // Provide hooks for third-party plugins
        do_action('wc_polylang_integration_loaded');
        
        // Register translation hooks
        add_action('wc_polylang_register_strings', array($this, 'handle_string_registration'), 10, 3);
        add_filter('wc_polylang_translate_string', array($this, 'handle_string_translation'), 10, 2);
        
        // Plugin compatibility hooks
        $this->init_plugin_compatibility();
    }
    
    /**
     * Initialize plugin compatibility
     */
    private function init_plugin_compatibility() {
        // Video Link per Product compatibility
        if (function_exists('woo_video_link_init')) {
            add_action('wc_polylang_integration_loaded', array($this, 'init_video_link_compatibility'));
        }
        
        // Company License Access compatibility
        if (function_exists('woo_company_license_init')) {
            add_action('wc_polylang_integration_loaded', array($this, 'init_company_license_compatibility'));
        }
        
        // WooCommerce Subscriptions compatibility
        if (class_exists('WC_Subscriptions')) {
            add_action('wc_polylang_integration_loaded', array($this, 'init_subscriptions_compatibility'));
        }
        
        // WooCommerce Bookings compatibility
        if (class_exists('WC_Bookings')) {
            add_action('wc_polylang_integration_loaded', array($this, 'init_bookings_compatibility'));
        }
    }
    
    /**
     * Handle string registration from third-party plugins
     */
    public function handle_string_registration($string, $name, $group) {
        if (function_exists('pll_register_string')) {
            pll_register_string($name, $string, $group);
        }
    }
    
    /**
     * Handle string translation
     */
    public function handle_string_translation($string, $name) {
        if (function_exists('pll__')) {
            return pll__($name) ?: $string;
        }
        return $string;
    }
    
    /**
     * Initialize Video Link per Product compatibility
     */
    public function init_video_link_compatibility() {
        // Register video link strings
        add_action('save_post', function($post_id) {
            if (get_post_type($post_id) === 'product') {
                $video_link = get_post_meta($post_id, '_video_link', true);
                $video_text = get_post_meta($post_id, '_video_link_text', true);
                
                if ($video_link) {
                    do_action('wc_polylang_register_strings', $video_link, 'Video Link (Product ' . $post_id . ')', 'Video Link Plugin');
                }
                
                if ($video_text) {
                    do_action('wc_polylang_register_strings', $video_text, 'Video Link Text (Product ' . $post_id . ')', 'Video Link Plugin');
                }
            }
        });
        
        // Translate video link display
        add_filter('woo_video_link_display_text', function($text, $product_id) {
            return apply_filters('wc_polylang_translate_string', $text, 'Video Link Text (Product ' . $product_id . ')');
        }, 10, 2);
    }
    
    /**
     * Initialize Company License Access compatibility
     */
    public function init_company_license_compatibility() {
        // Register license strings
        add_action('save_post', function($post_id) {
            if (get_post_type($post_id) === 'product') {
                $license_text = get_post_meta($post_id, '_company_license_text', true);
                $license_description = get_post_meta($post_id, '_company_license_description', true);
                
                if ($license_text) {
                    do_action('wc_polylang_register_strings', $license_text, 'License Text (Product ' . $post_id . ')', 'Company License Plugin');
                }
                
                if ($license_description) {
                    do_action('wc_polylang_register_strings', $license_description, 'License Description (Product ' . $post_id . ')', 'Company License Plugin');
                }
            }
        });
        
        // Translate license display
        add_filter('woo_company_license_display_text', function($text, $product_id) {
            return apply_filters('wc_polylang_translate_string', $text, 'License Text (Product ' . $product_id . ')');
        }, 10, 2);
    }
    
    /**
     * Initialize WooCommerce Subscriptions compatibility
     */
    public function init_subscriptions_compatibility() {
        // Add subscription post types to Polylang
        add_filter('pll_get_post_types', function($post_types) {
            $post_types['shop_subscription'] = 'shop_subscription';
            return $post_types;
        });
        
        // Translate subscription strings
        add_filter('woocommerce_subscriptions_product_price_string', function($price_string, $product) {
            if (function_exists('pll__')) {
                // Register and translate subscription-specific strings
                $patterns = array(
                    '/every (\d+) (day|week|month|year)s?/' => 'every %d %s',
                    '/for (\d+) (day|week|month|year)s?/' => 'for %d %s',
                    '/with (\d+) (day|week|month|year) free trial/' => 'with %d %s free trial',
                );
                
                foreach ($patterns as $pattern => $replacement) {
                    if (preg_match($pattern, $price_string, $matches)) {
                        $translated_replacement = pll__($replacement);
                        if ($translated_replacement) {
                            $price_string = preg_replace($pattern, sprintf($translated_replacement, $matches[1], pll__($matches[2])), $price_string);
                        }
                    }
                }
            }
            return $price_string;
        }, 10, 2);
    }
    
    /**
     * Initialize WooCommerce Bookings compatibility
     */
    public function init_bookings_compatibility() {
        // Add booking post types to Polylang
        add_filter('pll_get_post_types', function($post_types) {
            $post_types['wc_booking'] = 'wc_booking';
            return $post_types;
        });
        
        // Translate booking strings
        add_filter('woocommerce_bookings_get_time_label', function($label) {
            return apply_filters('wc_polylang_translate_string', $label, 'Booking Time Label');
        });
        
        add_filter('woocommerce_bookings_get_date_label', function($label) {
            return apply_filters('wc_polylang_translate_string', $label, 'Booking Date Label');
        });
    }
}

/**
 * API Functions for third-party plugins
 */

/**
 * Register a string for translation
 * 
 * @param string $string The string to register
 * @param string $name Unique name for the string
 * @param string $group Group/context for the string
 */
function wc_polylang_register_string($string, $name, $group = 'WooCommerce') {
    do_action('wc_polylang_register_strings', $string, $name, $group);
}

/**
 * Translate a registered string
 * 
 * @param string $string The original string
 * @param string $name The registered name of the string
 * @return string The translated string or original if no translation found
 */
function wc_polylang_translate_string($string, $name) {
    return apply_filters('wc_polylang_translate_string', $string, $name);
}

/**
 * Get current language
 * 
 * @return string|false Current language code or false if not available
 */
function wc_polylang_get_current_language() {
    if (function_exists('pll_current_language')) {
        return pll_current_language();
    }
    return false;
}

/**
 * Get all available languages
 * 
 * @return array Array of language codes
 */
function wc_polylang_get_languages() {
    if (function_exists('pll_languages_list')) {
        return pll_languages_list();
    }
    return array();
}

/**
 * Get product translations
 * 
 * @param int $product_id Product ID
 * @return array Array of translations (language => product_id)
 */
function wc_polylang_get_product_translations($product_id) {
    if (function_exists('pll_get_post_translations')) {
        return pll_get_post_translations($product_id);
    }
    return array();
}

/**
 * Set product language
 * 
 * @param int $product_id Product ID
 * @param string $language Language code
 * @return bool Success status
 */
function wc_polylang_set_product_language($product_id, $language) {
    if (function_exists('pll_set_post_language')) {
        return pll_set_post_language($product_id, $language);
    }
    return false;
}
