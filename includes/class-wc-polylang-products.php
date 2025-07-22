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
        
        add_action('init', array($this, 'init'));
        add_action('woocommerce_product_duplicate', array($this, 'handle_product_duplication'), 10, 2);
        add_filter('woocommerce_product_get_name', array($this, 'translate_product_name'), 10, 2);
        add_filter('woocommerce_product_get_description', array($this, 'translate_product_description'), 10, 2);
        add_filter('woocommerce_product_get_short_description', array($this, 'translate_product_short_description'), 10, 2);
        add_action('woocommerce_product_meta_start', array($this, 'add_translation_links'));
    }
    
    /**
     * Initialize product translation
     */
    public function init() {
        // Register product post type with Polylang
        if (function_exists('pll_register_string')) {
            add_action('pll_init', array($this, 'register_product_strings'));
        }
        
        // Add product translation support
        add_filter('pll_get_post_types', array($this, 'add_product_post_type'));
        add_filter('pll_get_taxonomies', array($this, 'add_product_taxonomies'));
        
        // Handle product attributes
        add_action('woocommerce_save_product_variation', array($this, 'save_variation_translations'));
        add_filter('woocommerce_attribute_label', array($this, 'translate_attribute_label'), 10, 3);
        
        // Handle product categories and tags
        add_filter('get_terms', array($this, 'translate_product_terms'), 10, 4);
    }
    
    /**
     * Register product strings with Polylang
     */
    public function register_product_strings() {
        // Register common WooCommerce strings
        $strings = array(
            'Add to cart' => __('Add to cart', 'woocommerce'),
            'Read more' => __('Read more', 'woocommerce'),
            'Select options' => __('Select options', 'woocommerce'),
            'Out of stock' => __('Out of stock', 'woocommerce'),
            'In stock' => __('In stock', 'woocommerce'),
            'Sale!' => __('Sale!', 'woocommerce'),
            'Free!' => __('Free!', 'woocommerce'),
            'Price' => __('Price', 'woocommerce'),
            'Quantity' => __('Quantity', 'woocommerce'),
            'Total' => __('Total', 'woocommerce'),
            'Subtotal' => __('Subtotal', 'woocommerce'),
            'Shipping' => __('Shipping', 'woocommerce'),
            'Tax' => __('Tax', 'woocommerce'),
        );
        
        foreach ($strings as $name => $string) {
            pll_register_string($name, $string, 'WooCommerce');
        }
        
        // Register product-specific strings
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($products as $product) {
            // Register product title
            pll_register_string(
                'Product Title: ' . $product->ID,
                $product->post_title,
                'WooCommerce Products'
            );
            
            // Register product description
            if (!empty($product->post_content)) {
                pll_register_string(
                    'Product Description: ' . $product->ID,
                    $product->post_content,
                    'WooCommerce Products'
                );
            }
            
            // Register product short description
            if (!empty($product->post_excerpt)) {
                pll_register_string(
                    'Product Short Description: ' . $product->ID,
                    $product->post_excerpt,
                    'WooCommerce Products'
                );
            }
        }
    }
    
    /**
     * Add product post type to Polylang
     */
    public function add_product_post_type($post_types) {
        $post_types['product'] = 'product';
        $post_types['product_variation'] = 'product_variation';
        return $post_types;
    }
    
    /**
     * Add product taxonomies to Polylang
     */
    public function add_product_taxonomies($taxonomies) {
        $taxonomies['product_cat'] = 'product_cat';
        $taxonomies['product_tag'] = 'product_tag';
        $taxonomies['pa_color'] = 'pa_color';
        $taxonomies['pa_size'] = 'pa_size';
        
        // Add all product attributes
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        foreach ($attribute_taxonomies as $attribute) {
            $taxonomies['pa_' . $attribute->attribute_name] = 'pa_' . $attribute->attribute_name;
        }
        
        return $taxonomies;
    }
    
    /**
     * Handle product duplication for translations
     */
    public function handle_product_duplication($duplicate, $product) {
        if (function_exists('pll_get_post_language')) {
            $original_lang = pll_get_post_language($product->get_id());
            if ($original_lang) {
                pll_set_post_language($duplicate->get_id(), $original_lang);
            }
        }
    }
    
    /**
     * Translate product name
     */
    public function translate_product_name($name, $product) {
        if (function_exists('pll__')) {
            $translated = pll__('Product Title: ' . $product->get_id());
            return $translated ? $translated : $name;
        }
        return $name;
    }
    
    /**
     * Translate product description
     */
    public function translate_product_description($description, $product) {
        if (function_exists('pll__')) {
            $translated = pll__('Product Description: ' . $product->get_id());
            return $translated ? $translated : $description;
        }
        return $description;
    }
    
    /**
     * Translate product short description
     */
    public function translate_product_short_description($short_description, $product) {
        if (function_exists('pll__')) {
            $translated = pll__('Product Short Description: ' . $product->get_id());
            return $translated ? $translated : $short_description;
        }
        return $short_description;
    }
    
    /**
     * Translate attribute labels
     */
    public function translate_attribute_label($label, $name, $product) {
        if (function_exists('pll__')) {
            $translated = pll__('Attribute: ' . $name);
            return $translated ? $translated : $label;
        }
        return $label;
    }
    
    /**
     * Translate product terms (categories, tags, attributes)
     */
    public function translate_product_terms($terms, $taxonomies, $args, $term_query) {
        if (!function_exists('pll_get_term_translations') || !is_array($terms)) {
            return $terms;
        }
        
        $product_taxonomies = array('product_cat', 'product_tag');
        $attribute_taxonomies = wc_get_attribute_taxonomy_names();
        $all_taxonomies = array_merge($product_taxonomies, $attribute_taxonomies);
        
        if (array_intersect($taxonomies, $all_taxonomies)) {
            $current_lang = pll_current_language();
            if ($current_lang) {
                foreach ($terms as $key => $term) {
                    if (is_object($term) && isset($term->term_id)) {
                        $translated_term_id = pll_get_term($term->term_id, $current_lang);
                        if ($translated_term_id && $translated_term_id !== $term->term_id) {
                            $translated_term = get_term($translated_term_id, $term->taxonomy);
                            if ($translated_term && !is_wp_error($translated_term)) {
                                $terms[$key] = $translated_term;
                            }
                        }
                    }
                }
            }
        }
        
        return $terms;
    }
    
    /**
     * Save variation translations
     */
    public function save_variation_translations($variation_id) {
        if (function_exists('pll_get_post_language')) {
            $parent_id = wp_get_post_parent_id($variation_id);
            if ($parent_id) {
                $parent_lang = pll_get_post_language($parent_id);
                if ($parent_lang) {
                    pll_set_post_language($variation_id, $parent_lang);
                }
            }
        }
    }
    
    /**
     * Add translation links to product meta
     */
    public function add_translation_links() {
        if (!function_exists('pll_the_languages') || !is_product()) {
            return;
        }
        
        global $product;
        if (!$product) {
            return;
        }
        
        $translations = pll_get_post_translations($product->get_id());
        if (count($translations) > 1) {
            echo '<div class="wc-polylang-product-translations">';
            echo '<span class="translations-label">' . __('Available in:', 'wc-polylang-integration') . '</span>';
            
            foreach ($translations as $lang => $translation_id) {
                if ($translation_id !== $product->get_id()) {
                    $translation_url = get_permalink($translation_id);
                    $lang_name = pll_get_language_name($lang);
                    echo '<a href="' . esc_url($translation_url) . '" class="translation-link" hreflang="' . esc_attr($lang) . '">';
                    echo esc_html($lang_name);
                    echo '</a>';
                }
            }
            
            echo '</div>';
        }
    }
}
