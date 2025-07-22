<?php
/**
 * Product translations
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
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        if (!wc_polylang_get_settings()['enable_product_translation'] === 'yes') {
            return;
        }
        
        try {
            // Add WooCommerce post types to Polylang
            add_filter('pll_get_post_types', array($this, 'add_post_types'));
            
            // Add WooCommerce taxonomies to Polylang
            add_filter('pll_get_taxonomies', array($this, 'add_taxonomies'));
            
            // Handle product variations
            add_action('woocommerce_save_product_variation', array($this, 'save_variation_translations'), 10, 2);
            
            // Handle product attributes
            add_filter('woocommerce_attribute_label', array($this, 'translate_attribute_label'), 10, 3);
            
            // Handle product search
            add_filter('woocommerce_product_query_meta_query', array($this, 'filter_products_by_language'));
            
            // Handle product URLs
            add_filter('post_type_link', array($this, 'product_permalink'), 10, 2);
            
            // Handle cart and checkout
            add_action('woocommerce_add_to_cart', array($this, 'handle_add_to_cart'), 10, 6);
            
            // Handle product categories in widgets
            add_filter('woocommerce_product_categories_widget_args', array($this, 'filter_category_widget_args'));
            
            // Handle layered navigation
            add_filter('woocommerce_layered_nav_term_html', array($this, 'translate_layered_nav_terms'), 10, 4);
            
            wc_polylang_debug_log('Product translations initialized');
            
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in products init: ' . $e->getMessage());
        }
    }
    
    /**
     * Add WooCommerce post types to Polylang
     */
    public function add_post_types($post_types) {
        $wc_post_types = array(
            'product' => 'product',
            'product_variation' => 'product_variation',
            'shop_order' => 'shop_order',
            'shop_coupon' => 'shop_coupon'
        );
        
        return array_merge($post_types, $wc_post_types);
    }
    
    /**
     * Add WooCommerce taxonomies to Polylang
     */
    public function add_taxonomies($taxonomies) {
        $wc_taxonomies = array(
            'product_cat' => 'product_cat',
            'product_tag' => 'product_tag',
            'product_shipping_class' => 'product_shipping_class'
        );
        
        // Add product attributes
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        foreach ($attribute_taxonomies as $attribute) {
            $taxonomy_name = wc_attribute_taxonomy_name($attribute->attribute_name);
            $wc_taxonomies[$taxonomy_name] = $taxonomy_name;
        }
        
        return array_merge($taxonomies, $wc_taxonomies);
    }
    
    /**
     * Save variation translations
     */
    public function save_variation_translations($variation_id, $i) {
        if (!function_exists('pll_get_post_language')) {
            return;
        }
        
        $parent_id = wp_get_post_parent_id($variation_id);
        if (!$parent_id) {
            return;
        }
        
        $parent_language = pll_get_post_language($parent_id);
        if ($parent_language) {
            pll_set_post_language($variation_id, $parent_language);
        }
    }
    
    /**
     * Translate attribute labels
     */
    public function translate_attribute_label($label, $name, $product) {
        if (!function_exists('pll__')) {
            return $label;
        }
        
        // Register attribute label for translation
        $string_name = 'Attribute: ' . $name;
        
        if (function_exists('pll_register_string')) {
            pll_register_string($string_name, $label, 'WooCommerce Attributes');
        }
        
        $translated = pll__($string_name);
        return $translated ?: $label;
    }
    
    /**
     * Filter products by current language
     */
    public function filter_products_by_language($meta_query) {
        if (!function_exists('pll_current_language') || is_admin()) {
            return $meta_query;
        }
        
        // This is handled by Polylang automatically for post queries
        return $meta_query;
    }
    
    /**
     * Handle product permalinks
     */
    public function product_permalink($permalink, $post) {
        if ($post->post_type !== 'product' || !function_exists('pll_get_post_language')) {
            return $permalink;
        }
        
        $language = pll_get_post_language($post->ID);
        if ($language && function_exists('pll_home_url')) {
            // Ensure the permalink uses the correct language base
            $home_url = pll_home_url($language);
            if ($home_url !== home_url('/')) {
                // Replace home URL with language-specific home URL
                $permalink = str_replace(home_url('/'), $home_url, $permalink);
            }
        }
        
        return $permalink;
    }
    
    /**
     * Handle add to cart for multilingual products
     */
    public function handle_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        if (!function_exists('pll_current_language')) {
            return;
        }
        
        $current_language = pll_current_language();
        
        // Store language with cart item
        WC()->cart->cart_contents[$cart_item_key]['product_language'] = $current_language;
        
        // Ensure we're using the correct language version of the product
        if (function_exists('pll_get_post')) {
            $translated_product_id = pll_get_post($product_id, $current_language);
            if ($translated_product_id && $translated_product_id !== $product_id) {
                // Update cart item to use translated product
                WC()->cart->cart_contents[$cart_item_key]['product_id'] = $translated_product_id;
            }
        }
    }
    
    /**
     * Filter category widget arguments
     */
    public function filter_category_widget_args($args) {
        if (!function_exists('pll_current_language')) {
            return $args;
        }
        
        // Categories are automatically filtered by Polylang
        return $args;
    }
    
    /**
     * Translate layered navigation terms
     */
    public function translate_layered_nav_terms($term_html, $term, $link, $count) {
        if (!function_exists('pll_get_term')) {
            return $term_html;
        }
        
        $current_language = pll_current_language();
        if (!$current_language) {
            return $term_html;
        }
        
        $translated_term = pll_get_term($term->term_id, $current_language);
        if ($translated_term) {
            $translated_term_obj = get_term($translated_term, $term->taxonomy);
            if ($translated_term_obj && !is_wp_error($translated_term_obj)) {
                // Replace term name in HTML
                $term_html = str_replace($term->name, $translated_term_obj->name, $term_html);
            }
        }
        
        return $term_html;
    }
    
    /**
     * Get product in specific language
     */
    public function get_product_in_language($product_id, $language = null) {
        if (!function_exists('pll_get_post')) {
            return wc_get_product($product_id);
        }
        
        $language = $language ?: pll_current_language();
        $translated_id = pll_get_post($product_id, $language);
        
        return $translated_id ? wc_get_product($translated_id) : wc_get_product($product_id);
    }
    
    /**
     * Get product categories in specific language
     */
    public function get_product_categories_in_language($language = null) {
        $language = $language ?: pll_current_language();
        
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ));
        
        if (!function_exists('pll_get_term')) {
            return $categories;
        }
        
        $translated_categories = array();
        foreach ($categories as $category) {
            $translated_id = pll_get_term($category->term_id, $language);
            if ($translated_id) {
                $translated_category = get_term($translated_id, 'product_cat');
                if ($translated_category && !is_wp_error($translated_category)) {
                    $translated_categories[] = $translated_category;
                }
            }
        }
        
        return $translated_categories;
    }
    
    /**
     * Handle product search in current language
     */
    public function handle_product_search($query) {
        if (!$query->is_search() || !$query->is_main_query() || is_admin()) {
            return;
        }
        
        if (!function_exists('pll_current_language')) {
            return;
        }
        
        // Product search is automatically handled by Polylang
        // This method can be extended for custom search functionality
    }
    
    /**
     * Sync product data between languages
     */
    public function sync_product_data($product_id, $target_language) {
        if (!function_exists('pll_get_post')) {
            return false;
        }
        
        $translated_id = pll_get_post($product_id, $target_language);
        if (!$translated_id) {
            return false;
        }
        
        $original_product = wc_get_product($product_id);
        $translated_product = wc_get_product($translated_id);
        
        if (!$original_product || !$translated_product) {
            return false;
        }
        
        // Sync non-translatable data
        $sync_data = array(
            '_regular_price' => $original_product->get_regular_price(),
            '_sale_price' => $original_product->get_sale_price(),
            '_price' => $original_product->get_price(),
            '_sku' => $original_product->get_sku(),
            '_stock' => $original_product->get_stock_quantity(),
            '_stock_status' => $original_product->get_stock_status(),
            '_manage_stock' => $original_product->get_manage_stock() ? 'yes' : 'no',
            '_weight' => $original_product->get_weight(),
            '_length' => $original_product->get_length(),
            '_width' => $original_product->get_width(),
            '_height' => $original_product->get_height(),
        );
        
        foreach ($sync_data as $meta_key => $meta_value) {
            if ($meta_value !== null && $meta_value !== '') {
                update_post_meta($translated_id, $meta_key, $meta_value);
            }
        }
        
        return true;
    }
}
