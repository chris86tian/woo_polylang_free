<?php
/**
 * Custom fields translation functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Custom_Fields {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (get_option('wc_polylang_enable_custom_fields_translation') !== 'yes') {
            return;
        }
        
        try {
            $this->init_hooks();
            wc_polylang_debug_log('Custom Fields class initialized');
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in custom fields constructor: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // RankMath SEO integration
        if (class_exists('RankMath')) {
            add_filter('rank_math/frontend/title', array($this, 'translate_rankmath_title'));
            add_filter('rank_math/frontend/description', array($this, 'translate_rankmath_description'));
        }
        
        // ACF integration
        if (class_exists('ACF')) {
            add_filter('acf/load_value', array($this, 'translate_acf_value'), 10, 3);
        }
        
        // Custom product fields
        add_action('save_post', array($this, 'register_custom_field_strings'));
    }
    
    /**
     * Translate RankMath title
     */
    public function translate_rankmath_title($title) {
        if (!function_exists('pll__') || !wc_polylang_is_woocommerce_page()) {
            return $title;
        }
        
        global $post;
        if ($post && get_post_type($post) === 'product') {
            $meta_title = get_post_meta($post->ID, 'rank_math_title', true);
            if ($meta_title) {
                $translated_title = pll__('RankMath Title: ' . $post->ID);
                return $translated_title ?: $title;
            }
        }
        
        return $title;
    }
    
    /**
     * Translate RankMath description
     */
    public function translate_rankmath_description($description) {
        if (!function_exists('pll__') || !wc_polylang_is_woocommerce_page()) {
            return $description;
        }
        
        global $post;
        if ($post && get_post_type($post) === 'product') {
            $meta_description = get_post_meta($post->ID, 'rank_math_description', true);
            if ($meta_description) {
                $translated_description = pll__('RankMath Description: ' . $post->ID);
                return $translated_description ?: $description;
            }
        }
        
        return $description;
    }
    
    /**
     * Translate ACF values
     */
    public function translate_acf_value($value, $post_id, $field) {
        if (!function_exists('pll__') || !is_string($value) || empty($value)) {
            return $value;
        }
        
        // Only translate for products
        if (get_post_type($post_id) !== 'product') {
            return $value;
        }
        
        // Translate text fields
        if (in_array($field['type'], array('text', 'textarea', 'wysiwyg'))) {
            $translated_value = pll__('ACF ' . $field['name'] . ': ' . $post_id);
            return $translated_value ?: $value;
        }
        
        return $value;
    }
    
    /**
     * Register custom field strings for translation
     */
    public function register_custom_field_strings($post_id) {
        if (get_post_type($post_id) !== 'product' || !function_exists('pll_register_string')) {
            return;
        }
        
        // Register RankMath fields
        if (class_exists('RankMath')) {
            $rank_math_title = get_post_meta($post_id, 'rank_math_title', true);
            $rank_math_description = get_post_meta($post_id, 'rank_math_description', true);
            
            if ($rank_math_title) {
                pll_register_string('RankMath Title: ' . $post_id, $rank_math_title, 'RankMath SEO');
            }
            
            if ($rank_math_description) {
                pll_register_string('RankMath Description: ' . $post_id, $rank_math_description, 'RankMath SEO');
            }
        }
        
        // Register ACF fields
        if (class_exists('ACF')) {
            $fields = get_fields($post_id);
            if ($fields) {
                foreach ($fields as $field_name => $field_value) {
                    if (is_string($field_value) && !empty($field_value)) {
                        pll_register_string('ACF ' . $field_name . ': ' . $post_id, $field_value, 'ACF Fields');
                    }
                }
            }
        }
        
        // Register custom product meta
        $custom_fields = array(
            '_video_link_text' => 'Video Link Text',
            '_company_license_text' => 'Company License Text',
            '_custom_product_description' => 'Custom Product Description',
        );
        
        foreach ($custom_fields as $meta_key => $label) {
            $meta_value = get_post_meta($post_id, $meta_key, true);
            if ($meta_value) {
                pll_register_string($label . ': ' . $post_id, $meta_value, 'Custom Product Fields');
            }
        }
    }
}
