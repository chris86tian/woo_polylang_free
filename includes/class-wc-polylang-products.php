<?php
/**
 * Product translation functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Products {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (get_option('wc_polylang_enable_product_translation') !== 'yes') {
            return;
        }
        
        try {
            $this->init_hooks();
            wc_polylang_debug_log('Products class initialized');
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in products constructor: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add product post type to Polylang
        add_filter('pll_get_post_types', array($this, 'add_post_types'));
        
        // Add product taxonomies to Polylang
        add_filter('pll_get_taxonomies', array($this, 'add_taxonomies'));
        
        // Handle product queries
        add_action('pre_get_posts', array($this, 'filter_product_queries'));
        
        // Handle product variations
        add_filter('woocommerce_product_variation_get_name', array($this, 'translate_variation_name'), 10, 2);
    }
    
    /**
     * Add product post types to Polylang
     */
    public function add_post_types($post_types) {
        $post_types['product'] = 'product';
        $post_types['product_variation'] = 'product_variation';
        return $post_types;
    }
    
    /**
     * Add product taxonomies to Polylang
     */
    public function add_taxonomies($taxonomies) {
        $taxonomies['product_cat'] = 'product_cat';
        $taxonomies['product_tag'] = 'product_tag';
        
        // Add product attributes
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        foreach ($attribute_taxonomies as $attribute) {
            $taxonomies['pa_' . $attribute->attribute_name] = 'pa_' . $attribute->attribute_name;
        }
        
        return $taxonomies;
    }
    
    /**
     * Filter product queries by language
     */
    public function filter_product_queries($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if (!function_exists('pll_current_language')) {
            return;
        }
        
        // Only filter product queries
        if ($query->get('post_type') === 'product' || is_shop() || is_product_category() || is_product_tag()) {
            $current_language = pll_current_language();
            if ($current_language) {
                $query->set('lang', $current_language);
            }
        }
    }
    
    /**
     * Translate product variation names
     */
    public function translate_variation_name($name, $product) {
        if (!function_exists('pll_get_post_language')) {
            return $name;
        }
        
        $language = pll_get_post_language($product->get_parent_id());
        if (!$language) {
            return $name;
        }
        
        // Get parent product in current language
        if (function_exists('pll_get_post')) {
            $translated_parent_id = pll_get_post($product->get_parent_id(), pll_current_language());
            if ($translated_parent_id && $translated_parent_id !== $product->get_parent_id()) {
                $translated_parent = wc_get_product($translated_parent_id);
                if ($translated_parent) {
                    // Find corresponding variation
                    $variations = $translated_parent->get_available_variations();
                    foreach ($variations as $variation) {
                        if ($this->variations_match($product, $variation)) {
                            $translated_variation = wc_get_product($variation['variation_id']);
                            if ($translated_variation) {
                                return $translated_variation->get_name();
                            }
                        }
                    }
                }
            }
        }
        
        return $name;
    }
    
    /**
     * Check if variations match (same attributes)
     */
    private function variations_match($original_variation, $translated_variation_data) {
        $original_attributes = $original_variation->get_attributes();
        $translated_attributes = $translated_variation_data['attributes'];
        
        // Simple comparison - could be enhanced
        return count($original_attributes) === count($translated_attributes);
    }
}
