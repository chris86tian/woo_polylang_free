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
        
        add_action('init', array($this, 'init'));
        add_action('save_post', array($this, 'save_custom_field_translations'), 10, 2);
        add_filter('get_post_metadata', array($this, 'translate_post_meta'), 10, 4);
    }
    
    /**
     * Initialize custom fields translation
     */
    public function init() {
        // Register custom field strings
        if (function_exists('pll_register_string')) {
            add_action('wp_loaded', array($this, 'register_custom_field_strings'));
        }
        
        // Handle ACF fields
        if (class_exists('ACF')) {
            add_filter('acf/load_value', array($this, 'translate_acf_value'), 10, 3);
            add_action('acf/save_post', array($this, 'save_acf_translations'));
        }
        
        // Handle RankMath SEO fields
        if (defined('RANK_MATH_VERSION')) {
            add_filter('rank_math/frontend/title', array($this, 'translate_rankmath_title'));
            add_filter('rank_math/frontend/description', array($this, 'translate_rankmath_description'));
            add_action('rank_math/head', array($this, 'register_rankmath_strings'));
        }
        
        // Handle Yoast SEO fields
        if (defined('WPSEO_VERSION')) {
            add_filter('wpseo_title', array($this, 'translate_yoast_title'));
            add_filter('wpseo_metadesc', array($this, 'translate_yoast_description'));
        }
        
        // Handle custom plugin fields
        $this->handle_custom_plugin_fields();
    }
    
    /**
     * Register custom field strings with Polylang
     */
    public function register_custom_field_strings() {
        // Get all products to register their custom fields
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($products as $product_id) {
            $this->register_product_custom_fields($product_id);
        }
    }
    
    /**
     * Register custom fields for a specific product
     */
    private function register_product_custom_fields($product_id) {
        // Get all meta fields for this product
        $meta_fields = get_post_meta($product_id);
        
        $translatable_fields = $this->get_translatable_fields();
        
        foreach ($meta_fields as $meta_key => $meta_values) {
            if (in_array($meta_key, $translatable_fields)) {
                foreach ($meta_values as $meta_value) {
                    if (is_string($meta_value) && !empty(trim($meta_value))) {
                        pll_register_string(
                            'Custom Field: ' . $meta_key . ' (Product ' . $product_id . ')',
                            $meta_value,
                            'WooCommerce Custom Fields'
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Get list of translatable custom fields
     */
    private function get_translatable_fields() {
        $default_fields = array(
            // WooCommerce fields
            '_product_attributes',
            '_purchase_note',
            
            // ACF fields (will be detected automatically)
            
            // RankMath SEO fields
            'rank_math_title',
            'rank_math_description',
            'rank_math_focus_keyword',
            
            // Yoast SEO fields
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            '_yoast_wpseo_focuskw',
            
            // Custom plugin fields
            '_video_link', // woo-video-link-per-product
            '_company_license_text', // woo-company-license-access
        );
        
        // Allow other plugins to add their fields
        return apply_filters('wc_polylang_translatable_fields', $default_fields);
    }
    
    /**
     * Save custom field translations
     */
    public function save_custom_field_translations($post_id, $post) {
        if ($post->post_type !== 'product' || !function_exists('pll_register_string')) {
            return;
        }
        
        // Register new custom field strings
        $this->register_product_custom_fields($post_id);
    }
    
    /**
     * Translate post meta values
     */
    public function translate_post_meta($value, $object_id, $meta_key, $single) {
        if (!function_exists('pll__') || !function_exists('pll_current_language')) {
            return $value;
        }
        
        // Only translate for products
        if (get_post_type($object_id) !== 'product') {
            return $value;
        }
        
        $translatable_fields = $this->get_translatable_fields();
        
        if (in_array($meta_key, $translatable_fields)) {
            // Get the original value
            remove_filter('get_post_metadata', array($this, 'translate_post_meta'), 10);
            $original_value = get_post_meta($object_id, $meta_key, $single);
            add_filter('get_post_metadata', array($this, 'translate_post_meta'), 10, 4);
            
            if (is_string($original_value) && !empty(trim($original_value))) {
                $string_name = 'Custom Field: ' . $meta_key . ' (Product ' . $object_id . ')';
                $translated = pll__($string_name);
                
                if ($translated && $translated !== $original_value) {
                    return $translated;
                }
            }
        }
        
        return $value;
    }
    
    /**
     * Translate ACF field values
     */
    public function translate_acf_value($value, $post_id, $field) {
        if (!function_exists('pll__') || !is_string($value) || empty(trim($value))) {
            return $value;
        }
        
        // Only translate for products
        if (get_post_type($post_id) !== 'product') {
            return $value;
        }
        
        $string_name = 'ACF Field: ' . $field['name'] . ' (Product ' . $post_id . ')';
        $translated = pll__($string_name);
        
        return $translated ?: $value;
    }
    
    /**
     * Save ACF field translations
     */
    public function save_acf_translations($post_id) {
        if (get_post_type($post_id) !== 'product' || !function_exists('pll_register_string')) {
            return;
        }
        
        // Get all ACF fields for this post
        $fields = get_fields($post_id);
        
        if ($fields) {
            foreach ($fields as $field_name => $field_value) {
                if (is_string($field_value) && !empty(trim($field_value))) {
                    pll_register_string(
                        'ACF Field: ' . $field_name . ' (Product ' . $post_id . ')',
                        $field_value,
                        'WooCommerce ACF Fields'
                    );
                }
            }
        }
    }
    
    /**
     * Translate RankMath title
     */
    public function translate_rankmath_title($title) {
        if (!function_exists('pll__')) {
            return $title;
        }
        
        global $post;
        if ($post && $post->post_type === 'product') {
            $string_name = 'RankMath Title (Product ' . $post->ID . ')';
            $translated = pll__($string_name);
            return $translated ?: $title;
        }
        
        return $title;
    }
    
    /**
     * Translate RankMath description
     */
    public function translate_rankmath_description($description) {
        if (!function_exists('pll__')) {
            return $description;
        }
        
        global $post;
        if ($post && $post->post_type === 'product') {
            $string_name = 'RankMath Description (Product ' . $post->ID . ')';
            $translated = pll__($string_name);
            return $translated ?: $description;
        }
        
        return $description;
    }
    
    /**
     * Register RankMath strings
     */
    public function register_rankmath_strings() {
        if (!function_exists('pll_register_string')) {
            return;
        }
        
        global $post;
        if ($post && $post->post_type === 'product') {
            $title = get_post_meta($post->ID, 'rank_math_title', true);
            $description = get_post_meta($post->ID, 'rank_math_description', true);
            
            if ($title) {
                pll_register_string(
                    'RankMath Title (Product ' . $post->ID . ')',
                    $title,
                    'WooCommerce RankMath'
                );
            }
            
            if ($description) {
                pll_register_string(
                    'RankMath Description (Product ' . $post->ID . ')',
                    $description,
                    'WooCommerce RankMath'
                );
            }
        }
    }
    
    /**
     * Translate Yoast title
     */
    public function translate_yoast_title($title) {
        if (!function_exists('pll__')) {
            return $title;
        }
        
        global $post;
        if ($post && $post->post_type === 'product') {
            $string_name = 'Yoast Title (Product ' . $post->ID . ')';
            $translated = pll__($string_name);
            return $translated ?: $title;
        }
        
        return $title;
    }
    
    /**
     * Translate Yoast description
     */
    public function translate_yoast_description($description) {
        if (!function_exists('pll__')) {
            return $description;
        }
        
        global $post;
        if ($post && $post->post_type === 'product') {
            $string_name = 'Yoast Description (Product ' . $post->ID . ')';
            $translated = pll__($string_name);
            return $translated ?: $description;
        }
        
        return $description;
    }
    
    /**
     * Handle custom plugin fields
     */
    private function handle_custom_plugin_fields() {
        // Video Link per Product plugin
        add_filter('woo_video_link_display', array($this, 'translate_video_link'), 10, 2);
        
        // Company License Access plugin
        add_filter('woo_company_license_text', array($this, 'translate_license_text'), 10, 2);
        
        // Allow other plugins to hook in
        do_action('wc_polylang_custom_fields_init', $this);
    }
    
    /**
     * Translate video link text
     */
    public function translate_video_link($link_html, $product_id) {
        if (!function_exists('pll__')) {
            return $link_html;
        }
        
        $video_link = get_post_meta($product_id, '_video_link', true);
        if ($video_link) {
            $string_name = 'Video Link (Product ' . $product_id . ')';
            $translated = pll__($string_name);
            
            if ($translated) {
                $link_html = str_replace($video_link, $translated, $link_html);
            }
        }
        
        return $link_html;
    }
    
    /**
     * Translate license text
     */
    public function translate_license_text($license_text, $product_id) {
        if (!function_exists('pll__')) {
            return $license_text;
        }
        
        $string_name = 'License Text (Product ' . $product_id . ')';
        $translated = pll__($string_name);
        
        return $translated ?: $license_text;
    }
}
