<?php
/**
 * SEO functionality for multilingual WooCommerce
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
        add_action('init', array($this, 'init'));
        add_action('wp_head', array($this, 'add_hreflang_tags'), 1);
        add_action('wp_head', array($this, 'add_canonical_urls'), 2);
        add_filter('wpseo_canonical', array($this, 'filter_canonical_url'));
        add_filter('rank_math/frontend/canonical', array($this, 'filter_canonical_url'));
        add_action('wp_head', array($this, 'add_language_meta_tags'), 3);
    }
    
    /**
     * Initialize SEO functionality
     */
    public function init() {
        // Add language-specific URL structure
        add_filter('rewrite_rules_array', array($this, 'add_language_rewrite_rules'));
        
        // Handle language switching for WooCommerce pages
        add_filter('pll_translation_url', array($this, 'fix_woocommerce_translation_urls'), 10, 2);
        
        // Add language information to structured data
        add_filter('woocommerce_structured_data_product', array($this, 'add_language_to_structured_data'), 10, 2);
        
        // Handle sitemap generation
        add_filter('wpseo_sitemap_url', array($this, 'add_language_to_sitemap_urls'), 10, 2);
    }
    
    /**
     * Add hreflang tags for multilingual SEO
     */
    public function add_hreflang_tags() {
        if (get_option('wc_polylang_seo_hreflang_tags') !== 'yes') {
            return;
        }
        
        if (!function_exists('pll_languages_list') || !function_exists('pll_get_post_translations')) {
            return;
        }
        
        global $post;
        
        // Only add hreflang for WooCommerce pages
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            return;
        }
        
        $languages = pll_languages_list();
        $current_post_id = get_queried_object_id();
        
        if (is_singular('product') && $post) {
            // Product pages
            $translations = pll_get_post_translations($post->ID);
            
            foreach ($languages as $lang) {
                $lang_info = pll_get_language($lang);
                if (!$lang_info) continue;
                
                if (isset($translations[$lang])) {
                    $url = get_permalink($translations[$lang]);
                } else {
                    // Fallback to homepage for this language
                    $url = pll_home_url($lang);
                }
                
                echo '<link rel="alternate" hreflang="' . esc_attr($lang_info['locale']) . '" href="' . esc_url($url) . '" />' . "\n";
            }
            
            // Add x-default
            $default_lang = pll_default_language();
            if (isset($translations[$default_lang])) {
                $default_url = get_permalink($translations[$default_lang]);
                echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";
            }
        } elseif (is_product_category() || is_product_tag()) {
            // Category/Tag pages
            $term = get_queried_object();
            if ($term && function_exists('pll_get_term_translations')) {
                $translations = pll_get_term_translations($term->term_id);
                
                foreach ($languages as $lang) {
                    $lang_info = pll_get_language($lang);
                    if (!$lang_info) continue;
                    
                    if (isset($translations[$lang])) {
                        $url = get_term_link($translations[$lang], $term->taxonomy);
                    } else {
                        $url = pll_home_url($lang);
                    }
                    
                    if (!is_wp_error($url)) {
                        echo '<link rel="alternate" hreflang="' . esc_attr($lang_info['locale']) . '" href="' . esc_url($url) . '" />' . "\n";
                    }
                }
            }
        } elseif (is_shop() || is_cart() || is_checkout() || is_account_page()) {
            // WooCommerce special pages
            foreach ($languages as $lang) {
                $lang_info = pll_get_language($lang);
                if (!$lang_info) continue;
                
                $url = $this->get_woocommerce_page_url_for_language($lang);
                if ($url) {
                    echo '<link rel="alternate" hreflang="' . esc_attr($lang_info['locale']) . '" href="' . esc_url($url) . '" />' . "\n";
                }
            }
        }
    }
    
    /**
     * Add canonical URLs
     */
    public function add_canonical_urls() {
        if (get_option('wc_polylang_seo_canonical_urls') !== 'yes') {
            return;
        }
        
        // Let SEO plugins handle canonical URLs if they're active
        if (defined('WPSEO_VERSION') || class_exists('RankMath')) {
            return;
        }
        
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            return;
        }
        
        $canonical_url = $this->get_canonical_url();
        if ($canonical_url) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        }
    }
    
    /**
     * Filter canonical URL for SEO plugins
     */
    public function filter_canonical_url($canonical_url) {
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            return $canonical_url;
        }
        
        $custom_canonical = $this->get_canonical_url();
        return $custom_canonical ?: $canonical_url;
    }
    
    /**
     * Get canonical URL for current page
     */
    private function get_canonical_url() {
        global $wp;
        
        if (is_singular('product')) {
            return get_permalink();
        } elseif (is_product_category() || is_product_tag()) {
            $term = get_queried_object();
            return get_term_link($term);
        } elseif (is_shop()) {
            return get_permalink(wc_get_page_id('shop'));
        } elseif (is_cart()) {
            return get_permalink(wc_get_page_id('cart'));
        } elseif (is_checkout()) {
            return get_permalink(wc_get_page_id('checkout'));
        } elseif (is_account_page()) {
            return get_permalink(wc_get_page_id('myaccount'));
        }
        
        return home_url($wp->request);
    }
    
    /**
     * Add language meta tags
     */
    public function add_language_meta_tags() {
        if (!function_exists('pll_current_language')) {
            return;
        }
        
        $current_lang = pll_current_language();
        if ($current_lang) {
            $lang_info = pll_get_language($current_lang);
            if ($lang_info) {
                echo '<meta property="og:locale" content="' . esc_attr($lang_info['locale']) . '" />' . "\n";
                echo '<meta name="language" content="' . esc_attr($current_lang) . '" />' . "\n";
            }
        }
    }
    
    /**
     * Add language rewrite rules
     */
    public function add_language_rewrite_rules($rules) {
        if (!function_exists('pll_languages_list')) {
            return $rules;
        }
        
        $languages = pll_languages_list();
        $new_rules = array();
        
        foreach ($languages as $lang) {
            if ($lang === pll_default_language()) {
                continue; // Skip default language
            }
            
            // Add rules for WooCommerce pages
            $new_rules[$lang . '/shop/?$'] = 'index.php?post_type=product&lang=' . $lang;
            $new_rules[$lang . '/cart/?$'] = 'index.php?pagename=cart&lang=' . $lang;
            $new_rules[$lang . '/checkout/?$'] = 'index.php?pagename=checkout&lang=' . $lang;
            $new_rules[$lang . '/my-account/?$'] = 'index.php?pagename=my-account&lang=' . $lang;
            
            // Add rules for product categories
            $new_rules[$lang . '/product-category/([^/]+)/?$'] = 'index.php?product_cat=$matches[1]&lang=' . $lang;
            
            // Add rules for product tags
            $new_rules[$lang . '/product-tag/([^/]+)/?$'] = 'index.php?product_tag=$matches[1]&lang=' . $lang;
            
            // Add rules for individual products
            $new_rules[$lang . '/product/([^/]+)/?$'] = 'index.php?product=$matches[1]&lang=' . $lang;
        }
        
        return $new_rules + $rules;
    }
    
    /**
     * Fix WooCommerce translation URLs
     */
    public function fix_woocommerce_translation_urls($url, $lang) {
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            return $url;
        }
        
        $translated_url = $this->get_woocommerce_page_url_for_language($lang);
        return $translated_url ?: $url;
    }
    
    /**
     * Get WooCommerce page URL for specific language
     */
    private function get_woocommerce_page_url_for_language($lang) {
        if (is_shop()) {
            $shop_page_id = wc_get_page_id('shop');
            return pll_get_post_language($shop_page_id) ? get_permalink(pll_get_post($shop_page_id, $lang)) : null;
        } elseif (is_cart()) {
            $cart_page_id = wc_get_page_id('cart');
            return pll_get_post_language($cart_page_id) ? get_permalink(pll_get_post($cart_page_id, $lang)) : null;
        } elseif (is_checkout()) {
            $checkout_page_id = wc_get_page_id('checkout');
            return pll_get_post_language($checkout_page_id) ? get_permalink(pll_get_post($checkout_page_id, $lang)) : null;
        } elseif (is_account_page()) {
            $account_page_id = wc_get_page_id('myaccount');
            return pll_get_post_language($account_page_id) ? get_permalink(pll_get_post($account_page_id, $lang)) : null;
        }
        
        return pll_home_url($lang);
    }
    
    /**
     * Add language information to structured data
     */
    public function add_language_to_structured_data($markup, $product) {
        if (!function_exists('pll_current_language')) {
            return $markup;
        }
        
        $current_lang = pll_current_language();
        if ($current_lang) {
            $lang_info = pll_get_language($current_lang);
            if ($lang_info) {
                $markup['inLanguage'] = $lang_info['locale'];
            }
        }
        
        return $markup;
    }
    
    /**
     * Add language to sitemap URLs
     */
    public function add_language_to_sitemap_urls($url, $post) {
        if (!function_exists('pll_get_post_language')) {
            return $url;
        }
        
        if ($post->post_type === 'product') {
            $lang = pll_get_post_language($post->ID);
            if ($lang && $lang !== pll_default_language()) {
                $url['loc'] = str_replace(home_url('/'), home_url('/' . $lang . '/'), $url['loc']);
            }
        }
        
        return $url;
    }
}
