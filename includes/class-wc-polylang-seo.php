<?php
/**
 * SEO optimization functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_SEO {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (get_option('wc_polylang_enable_seo_translation') !== 'yes') {
            return;
        }
        
        try {
            $this->init_hooks();
            wc_polylang_debug_log('SEO class initialized');
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in SEO constructor: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add hreflang tags
        if (get_option('wc_polylang_seo_hreflang_tags') === 'yes') {
            add_action('wp_head', array($this, 'add_hreflang_tags'));
        }
        
        // Add canonical URLs
        if (get_option('wc_polylang_seo_canonical_urls') === 'yes') {
            add_action('wp_head', array($this, 'add_canonical_url'));
        }
        
        // Modify page titles
        add_filter('wp_title', array($this, 'modify_page_title'), 10, 2);
        add_filter('document_title_parts', array($this, 'modify_document_title_parts'));
    }
    
    /**
     * Add hreflang tags for WooCommerce pages
     */
    public function add_hreflang_tags() {
        if (!function_exists('pll_languages_list') || !wc_polylang_is_woocommerce_page()) {
            return;
        }
        
        global $post;
        $languages = pll_languages_list();
        
        foreach ($languages as $language) {
            $url = $this->get_translated_url($language);
            if ($url) {
                echo '<link rel="alternate" hreflang="' . esc_attr($language) . '" href="' . esc_url($url) . '" />' . "\n";
            }
        }
    }
    
    /**
     * Add canonical URL
     */
    public function add_canonical_url() {
        if (!wc_polylang_is_woocommerce_page()) {
            return;
        }
        
        $canonical_url = $this->get_canonical_url();
        if ($canonical_url) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        }
    }
    
    /**
     * Get translated URL for a specific language
     */
    private function get_translated_url($language) {
        global $post;
        
        if (is_shop()) {
            return wc_polylang_get_page_url('shop', $language);
        } elseif (is_cart()) {
            return wc_polylang_get_page_url('cart', $language);
        } elseif (is_checkout()) {
            return wc_polylang_get_page_url('checkout', $language);
        } elseif (is_account_page()) {
            return wc_polylang_get_page_url('myaccount', $language);
        } elseif (is_singular('product') && $post) {
            if (function_exists('pll_get_post')) {
                $translated_id = pll_get_post($post->ID, $language);
                return $translated_id ? get_permalink($translated_id) : null;
            }
        } elseif (is_product_category()) {
            $term = get_queried_object();
            if ($term && function_exists('pll_get_term')) {
                $translated_term_id = pll_get_term($term->term_id, $language);
                return $translated_term_id ? get_term_link($translated_term_id, 'product_cat') : null;
            }
        }
        
        return null;
    }
    
    /**
     * Get canonical URL for current page
     */
    private function get_canonical_url() {
        global $post;
        
        if (is_shop()) {
            return get_permalink(wc_get_page_id('shop'));
        } elseif (is_cart()) {
            return get_permalink(wc_get_page_id('cart'));
        } elseif (is_checkout()) {
            return get_permalink(wc_get_page_id('checkout'));
        } elseif (is_account_page()) {
            return get_permalink(wc_get_page_id('myaccount'));
        } elseif (is_singular('product') && $post) {
            return get_permalink($post->ID);
        } elseif (is_product_category()) {
            $term = get_queried_object();
            return $term ? get_term_link($term, 'product_cat') : null;
        }
        
        return null;
    }
    
    /**
     * Modify page title
     */
    public function modify_page_title($title, $sep) {
        if (!wc_polylang_is_woocommerce_page() || !function_exists('pll__')) {
            return $title;
        }
        
        // Translate WooCommerce page titles
        if (is_shop()) {
            $shop_title = pll__('Shop');
            if ($shop_title) {
                $title = $shop_title . ' ' . $sep . ' ' . get_bloginfo('name');
            }
        }
        
        return $title;
    }
    
    /**
     * Modify document title parts
     */
    public function modify_document_title_parts($title_parts) {
        if (!wc_polylang_is_woocommerce_page() || !function_exists('pll__')) {
            return $title_parts;
        }
        
        if (is_shop() && isset($title_parts['title'])) {
            $shop_title = pll__('Shop');
            if ($shop_title) {
                $title_parts['title'] = $shop_title;
            }
        }
        
        return $title_parts;
    }
}
