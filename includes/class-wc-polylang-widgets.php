<?php
/**
 * Widget translation functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Widgets {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (get_option('wc_polylang_enable_widget_translation') !== 'yes') {
            return;
        }
        
        try {
            $this->init_hooks();
            wc_polylang_debug_log('Widgets class initialized');
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in widgets constructor: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Translate WooCommerce strings
        add_filter('gettext', array($this, 'translate_woocommerce_strings'), 10, 3);
        add_filter('ngettext', array($this, 'translate_woocommerce_plural_strings'), 10, 5);
        
        // Translate cart and checkout strings
        add_filter('woocommerce_cart_item_name', array($this, 'translate_cart_item_name'), 10, 3);
        add_filter('woocommerce_checkout_product_title', array($this, 'translate_checkout_product_title'), 10, 2);
    }
    
    /**
     * Translate WooCommerce strings
     */
    public function translate_woocommerce_strings($translated, $original, $domain) {
        if ($domain !== 'woocommerce') {
            return $translated;
        }
        
        if (!function_exists('pll__')) {
            return $translated;
        }
        
        // Common WooCommerce strings to translate
        $strings_to_translate = array(
            'Add to cart' => 'In den Warenkorb',
            'View cart' => 'Warenkorb anzeigen',
            'Checkout' => 'Zur Kasse',
            'My account' => 'Mein Konto',
            'Cart' => 'Warenkorb',
            'Shop' => 'Shop',
            'Product' => 'Produkt',
            'Products' => 'Produkte',
            'Category' => 'Kategorie',
            'Categories' => 'Kategorien',
            'Price' => 'Preis',
            'Sale!' => 'Angebot!',
            'Out of stock' => 'Nicht vorrätig',
            'In stock' => 'Vorrätig',
        );
        
        if (isset($strings_to_translate[$original])) {
            $polylang_translation = pll__($original);
            return $polylang_translation ?: $translated;
        }
        
        return $translated;
    }
    
    /**
     * Translate WooCommerce plural strings
     */
    public function translate_woocommerce_plural_strings($translated, $single, $plural, $number, $domain) {
        if ($domain !== 'woocommerce') {
            return $translated;
        }
        
        if (!function_exists('pll__')) {
            return $translated;
        }
        
        // Handle plural forms
        $string_to_translate = $number === 1 ? $single : $plural;
        $polylang_translation = pll__($string_to_translate);
        
        return $polylang_translation ?: $translated;
    }
    
    /**
     * Translate cart item names
     */
    public function translate_cart_item_name($name, $cart_item, $cart_item_key) {
        if (!isset($cart_item['product_id'])) {
            return $name;
        }
        
        $translated_product = wc_polylang_get_product($cart_item['product_id']);
        if ($translated_product) {
            return $translated_product->get_name();
        }
        
        return $name;
    }
    
    /**
     * Translate checkout product titles
     */
    public function translate_checkout_product_title($title, $product) {
        if (!$product) {
            return $title;
        }
        
        $translated_product = wc_polylang_get_product($product->get_id());
        if ($translated_product) {
            return $translated_product->get_name();
        }
        
        return $title;
    }
}
