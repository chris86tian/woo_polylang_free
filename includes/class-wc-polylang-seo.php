<?php
/**
 * SEO optimization
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
    }
    
    /**
     * Initialize
     */
    public function init() {
        if (wc_polylang_get_settings()['enable_seo_translation'] !== 'yes') {
            return;
        }
        
        try {
            // Add hreflang tags
            if (wc_polylang_get_settings()['seo_hreflang_tags'] === 'yes') {
                add_action('wp_head', array($this, 'add_hreflang_tags'), 1);
            }
            
            // Add canonical URLs
            if (wc_polylang_get_settings()['seo_canonical_urls'] === 'yes') {
                add_action('wp_head', array($this, 'add_canonical_url'), 2);
            }
            
            // Handle product schema
            add_filter('woocommerce_structured_data_product', array($this, 'add_multilingual_product_schema'), 10, 2);
            
            // Handle breadcrumb schema
            add_filter('woocommerce_structured_data_breadcrumb', array($this, 'add_multilingual_breadcrumb_schema'), 10, 2);
            
            // Handle organization schema
            add_filter('woocommerce_structured_data_organization', array($this, 'add_multilingual_organization_schema'), 10, 2);
            
            // Handle meta descriptions
            add_filter('woocommerce_short_description', array($this, 'optimize_meta_description'), 10, 1);
            
            // Handle page titles
            add_filter('woocommerce_page_title', array($this, 'optimize_page_title'), 10, 1);
            
            // Handle Open Graph tags
            add_action('wp_head', array($this, 'add_og_tags'), 5);
            
            // Handle Twitter Card tags
            add_action('wp_head', array($this, 'add_twitter_tags'), 6);
            
            // Handle XML sitemap
            add_filter('wpseo_sitemap_url', array($this, 'handle_sitemap_urls'), 10, 2);
            
            // RankMath integration
            if (class_exists('RankMath')) {
                $this->init_rankmath_integration();
            }
            
            // Yoast SEO integration
            if (class_exists('WPSEO_Options')) {
                $this->init_yoast_integration();
            }
            
            wc_polylang_debug_log('SEO optimization initialized');
            
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in SEO init: ' . $e->getMessage());
        }
    }
    
    /**
     * Add hreflang tags
     */
    public function add_hreflang_tags() {
        if (!function_exists('pll_languages_list') || !wc_polylang_is_woocommerce_page()) {
            return;
        }
        
        global $post;
        
        $languages = pll_languages_list();
        $current_language = pll_current_language();
        
        if (empty($languages)) {
            return;
        }
        
        echo "\n<!-- WooCommerce Polylang Integration - Hreflang Tags -->\n";
        
        foreach ($languages as $language) {
            $url = $this->get_translated_url($language);
            
            if ($url) {
                $locale = $this->get_language_locale($language);
                echo '<link rel="alternate" hreflang="' . esc_attr($locale) . '" href="' . esc_url($url) . '" />' . "\n";
            }
        }
        
        // Add x-default
        $default_language = function_exists('pll_default_language') ? pll_default_language() : 'de';
        $default_url = $this->get_translated_url($default_language);
        if ($default_url) {
            echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";
        }
        
        echo "<!-- End Hreflang Tags -->\n\n";
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
     * Get translated URL for language
     */
    private function get_translated_url($language) {
        global $post;
        
        if (is_shop()) {
            return wc_polylang_get_page_url('shop', $language);
        } elseif (is_product_category()) {
            $term = get_queried_object();
            if ($term && function_exists('pll_get_term')) {
                $translated_term_id = pll_get_term($term->term_id, $language);
                if ($translated_term_id) {
                    return get_term_link($translated_term_id, 'product_cat');
                }
            }
        } elseif (is_product_tag()) {
            $term = get_queried_object();
            if ($term && function_exists('pll_get_term')) {
                $translated_term_id = pll_get_term($term->term_id, $language);
                if ($translated_term_id) {
                    return get_term_link($translated_term_id, 'product_tag');
                }
            }
        } elseif (is_product() && $post) {
            if (function_exists('pll_get_post')) {
                $translated_post_id = pll_get_post($post->ID, $language);
                if ($translated_post_id) {
                    return get_permalink($translated_post_id);
                }
            }
        } elseif (is_cart()) {
            return wc_polylang_get_page_url('cart', $language);
        } elseif (is_checkout()) {
            return wc_polylang_get_page_url('checkout', $language);
        } elseif (is_account_page()) {
            return wc_polylang_get_page_url('myaccount', $language);
        }
        
        return false;
    }
    
    /**
     * Get canonical URL
     */
    private function get_canonical_url() {
        global $post;
        
        if (is_shop()) {
            $shop_page_id = wc_get_page_id('shop');
            return get_permalink($shop_page_id);
        } elseif (is_product_category()) {
            $term = get_queried_object();
            return get_term_link($term);
        } elseif (is_product_tag()) {
            $term = get_queried_object();
            return get_term_link($term);
        } elseif (is_product() && $post) {
            return get_permalink($post->ID);
        } elseif (is_cart()) {
            return wc_get_cart_url();
        } elseif (is_checkout()) {
            return wc_get_checkout_url();
        } elseif (is_account_page()) {
            return wc_get_page_permalink('myaccount');
        }
        
        return false;
    }
    
    /**
     * Get language locale
     */
    private function get_language_locale($language) {
        $locales = array(
            'de' => 'de-DE',
            'en' => 'en-US',
            'fr' => 'fr-FR',
            'es' => 'es-ES',
            'it' => 'it-IT',
            'nl' => 'nl-NL',
            'pt' => 'pt-PT',
            'ru' => 'ru-RU',
            'ja' => 'ja-JP',
            'zh' => 'zh-CN'
        );
        
        return isset($locales[$language]) ? $locales[$language] : $language;
    }
    
    /**
     * Add multilingual product schema
     */
    public function add_multilingual_product_schema($markup, $product) {
        if (!function_exists('pll_current_language')) {
            return $markup;
        }
        
        $current_language = pll_current_language();
        
        // Add language to schema
        $markup['inLanguage'] = $this->get_language_locale($current_language);
        
        // Add alternate language versions
        if (function_exists('pll_get_post_translations')) {
            $translations = pll_get_post_translations($product->get_id());
            $alternates = array();
            
            foreach ($translations as $lang => $post_id) {
                if ($lang !== $current_language) {
                    $alternates[] = array(
                        '@type' => 'Product',
                        'url' => get_permalink($post_id),
                        'inLanguage' => $this->get_language_locale($lang)
                    );
                }
            }
            
            if (!empty($alternates)) {
                $markup['sameAs'] = $alternates;
            }
        }
        
        return $markup;
    }
    
    /**
     * Add multilingual breadcrumb schema
     */
    public function add_multilingual_breadcrumb_schema($markup, $breadcrumbs) {
        if (!function_exists('pll_current_language')) {
            return $markup;
        }
        
        $current_language = pll_current_language();
        
        // Add language to schema
        $markup['inLanguage'] = $this->get_language_locale($current_language);
        
        return $markup;
    }
    
    /**
     * Add multilingual organization schema
     */
    public function add_multilingual_organization_schema($markup, $organization) {
        if (!function_exists('pll_current_language')) {
            return $markup;
        }
        
        $current_language = pll_current_language();
        
        // Add language to schema
        $markup['inLanguage'] = $this->get_language_locale($current_language);
        
        return $markup;
    }
    
    /**
     * Optimize meta description
     */
    public function optimize_meta_description($description) {
        if (!function_exists('pll__')) {
            return $description;
        }
        
        // Translate meta description if it's a registered string
        $translated_description = pll__($description);
        return $translated_description ?: $description;
    }
    
    /**
     * Optimize page title
     */
    public function optimize_page_title($title) {
        if (!function_exists('pll__')) {
            return $title;
        }
        
        // Translate page title if it's a registered string
        $translated_title = pll__($title);
        return $translated_title ?: $title;
    }
    
    /**
     * Add Open Graph tags
     */
    public function add_og_tags() {
        if (!wc_polylang_is_woocommerce_page()) {
            return;
        }
        
        global $post;
        
        echo "\n<!-- WooCommerce Polylang Integration - Open Graph Tags -->\n";
        
        if (is_product() && $post) {
            $product = wc_get_product($post->ID);
            if ($product) {
                echo '<meta property="og:type" content="product" />' . "\n";
                echo '<meta property="og:title" content="' . esc_attr($product->get_name()) . '" />' . "\n";
                echo '<meta property="og:description" content="' . esc_attr(wp_strip_all_tags($product->get_short_description())) . '" />' . "\n";
                echo '<meta property="og:url" content="' . esc_url(get_permalink($post->ID)) . '" />' . "\n";
                
                $image_id = $product->get_image_id();
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'large');
                    if ($image_url) {
                        echo '<meta property="og:image" content="' . esc_url($image_url) . '" />' . "\n";
                    }
                }
                
                // Product specific OG tags
                echo '<meta property="product:price:amount" content="' . esc_attr($product->get_price()) . '" />' . "\n";
                echo '<meta property="product:price:currency" content="' . esc_attr(get_woocommerce_currency()) . '" />' . "\n";
                
                if ($product->is_in_stock()) {
                    echo '<meta property="product:availability" content="in stock" />' . "\n";
                } else {
                    echo '<meta property="product:availability" content="out of stock" />' . "\n";
                }
            }
        } elseif (is_shop()) {
            echo '<meta property="og:type" content="website" />' . "\n";
            echo '<meta property="og:title" content="' . esc_attr(woocommerce_page_title(false)) . '" />' . "\n";
            echo '<meta property="og:url" content="' . esc_url(wc_get_page_permalink('shop')) . '" />' . "\n";
        }
        
        // Add language
        if (function_exists('pll_current_language')) {
            $current_language = pll_current_language();
            echo '<meta property="og:locale" content="' . esc_attr($this->get_language_locale($current_language)) . '" />' . "\n";
        }
        
        echo "<!-- End Open Graph Tags -->\n\n";
    }
    
    /**
     * Add Twitter Card tags
     */
    public function add_twitter_tags() {
        if (!wc_polylang_is_woocommerce_page()) {
            return;
        }
        
        global $post;
        
        echo "\n<!-- WooCommerce Polylang Integration - Twitter Card Tags -->\n";
        
        if (is_product() && $post) {
            $product = wc_get_product($post->ID);
            if ($product) {
                echo '<meta name="twitter:card" content="product" />' . "\n";
                echo '<meta name="twitter:title" content="' . esc_attr($product->get_name()) . '" />' . "\n";
                echo '<meta name="twitter:description" content="' . esc_attr(wp_strip_all_tags($product->get_short_description())) . '" />' . "\n";
                
                $image_id = $product->get_image_id();
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'large');
                    if ($image_url) {
                        echo '<meta name="twitter:image" content="' . esc_url($image_url) . '" />' . "\n";
                    }
                }
                
                // Product specific Twitter tags
                echo '<meta name="twitter:label1" content="Price" />' . "\n";
                echo '<meta name="twitter:data1" content="' . esc_attr(wc_price($product->get_price())) . '" />' . "\n";
                
                echo '<meta name="twitter:label2" content="Availability" />' . "\n";
                if ($product->is_in_stock()) {
                    echo '<meta name="twitter:data2" content="In Stock" />' . "\n";
                } else {
                    echo '<meta name="twitter:data2" content="Out of Stock" />' . "\n";
                }
            }
        } elseif (is_shop()) {
            echo '<meta name="twitter:card" content="summary" />' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr(woocommerce_page_title(false)) . '" />' . "\n";
        }
        
        echo "<!-- End Twitter Card Tags -->\n\n";
    }
    
    /**
     * Handle sitemap URLs
     */
    public function handle_sitemap_urls($url, $post) {
        if (!function_exists('pll_get_post_language')) {
            return $url;
        }
        
        // Ensure URLs in sitemap are language-specific
        $language = pll_get_post_language($post->ID);
        if ($language && function_exists('pll_home_url')) {
            $home_url = pll_home_url($language);
            if ($home_url !== home_url('/')) {
                $url = str_replace(home_url('/'), $home_url, $url);
            }
        }
        
        return $url;
    }
    
    /**
     * Initialize RankMath integration
     */
    private function init_rankmath_integration() {
        // Handle RankMath meta fields
        add_filter('rank_math/frontend/title', array($this, 'translate_rankmath_title'), 10, 1);
        add_filter('rank_math/frontend/description', array($this, 'translate_rankmath_description'), 10, 1);
        
        // Handle RankMath breadcrumbs
        add_filter('rank_math/frontend/breadcrumb/items', array($this, 'translate_rankmath_breadcrumbs'), 10, 2);
        
        wc_polylang_debug_log('RankMath integration initialized');
    }
    
    /**
     * Translate RankMath title
     */
    public function translate_rankmath_title($title) {
        if (!function_exists('pll__')) {
            return $title;
        }
        
        $translated_title = pll__($title);
        return $translated_title ?: $title;
    }
    
    /**
     * Translate RankMath description
     */
    public function translate_rankmath_description($description) {
        if (!function_exists('pll__')) {
            return $description;
        }
        
        $translated_description = pll__($description);
        return $translated_description ?: $description;
    }
    
    /**
     * Translate RankMath breadcrumbs
     */
    public function translate_rankmath_breadcrumbs($crumbs, $class) {
        if (!function_exists('pll__')) {
            return $crumbs;
        }
        
        foreach ($crumbs as &$crumb) {
            if (isset($crumb[0])) {
                $translated_text = pll__($crumb[0]);
                if ($translated_text) {
                    $crumb[0] = $translated_text;
                }
            }
        }
        
        return $crumbs;
    }
    
    /**
     * Initialize Yoast SEO integration
     */
    private function init_yoast_integration() {
        // Handle Yoast meta fields
        add_filter('wpseo_title', array($this, 'translate_yoast_title'), 10, 1);
        add_filter('wpseo_metadesc', array($this, 'translate_yoast_description'), 10, 1);
        
        // Handle Yoast breadcrumbs
        add_filter('wpseo_breadcrumb_links', array($this, 'translate_yoast_breadcrumbs'), 10, 1);
        
        wc_polylang_debug_log('Yoast SEO integration initialized');
    }
    
    /**
     * Translate Yoast title
     */
    public function translate_yoast_title($title) {
        if (!function_exists('pll__')) {
            return $title;
        }
        
        $translated_title = pll__($title);
        return $translated_title ?: $title;
    }
    
    /**
     * Translate Yoast description
     */
    public function translate_yoast_description($description) {
        if (!function_exists('pll__')) {
            return $description;
        }
        
        $translated_description = pll__($description);
        return $translated_description ?: $description;
    }
    
    /**
     * Translate Yoast breadcrumbs
     */
    public function translate_yoast_breadcrumbs($links) {
        if (!function_exists('pll__')) {
            return $links;
        }
        
        foreach ($links as &$link) {
            if (isset($link['text'])) {
                $translated_text = pll__($link['text']);
                if ($translated_text) {
                    $link['text'] = $translated_text;
                }
            }
        }
        
        return $links;
    }
}
