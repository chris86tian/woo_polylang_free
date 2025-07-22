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
        
        add_action('init', array($this, 'init'));
        add_filter('woocommerce_get_script_data', array($this, 'translate_script_data'), 10, 2);
        add_filter('wc_add_to_cart_message_html', array($this, 'translate_add_to_cart_message'), 10, 3);
        add_filter('woocommerce_cart_item_remove_link', array($this, 'translate_remove_link'), 10, 2);
        add_filter('woocommerce_checkout_fields', array($this, 'translate_checkout_fields'));
    }
    
    /**
     * Initialize widget translation
     */
    public function init() {
        // Register widget strings
        if (function_exists('pll_register_string')) {
            $this->register_widget_strings();
        }
        
        // Translate button texts
        add_filter('woocommerce_product_add_to_cart_text', array($this, 'translate_add_to_cart_text'), 10, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'translate_single_add_to_cart_text'), 10, 2);
        
        // Translate cart and checkout strings
        add_filter('gettext', array($this, 'translate_woocommerce_strings'), 10, 3);
        add_filter('ngettext', array($this, 'translate_woocommerce_plural_strings'), 10, 5);
        
        // Translate AJAX responses
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'translate_cart_fragments'));
    }
    
    /**
     * Register widget strings with Polylang
     */
    private function register_widget_strings() {
        $widget_strings = array(
            // Cart strings
            'View cart' => __('View cart', 'woocommerce'),
            'Checkout' => __('Checkout', 'woocommerce'),
            'Proceed to checkout' => __('Proceed to checkout', 'woocommerce'),
            'Update cart' => __('Update cart', 'woocommerce'),
            'Apply coupon' => __('Apply coupon', 'woocommerce'),
            'Remove this item' => __('Remove this item', 'woocommerce'),
            'Cart totals' => __('Cart totals', 'woocommerce'),
            'Your cart is currently empty.' => __('Your cart is currently empty.', 'woocommerce'),
            'Return to shop' => __('Return to shop', 'woocommerce'),
            
            // Checkout strings
            'Place order' => __('Place order', 'woocommerce'),
            'Billing details' => __('Billing details', 'woocommerce'),
            'Shipping details' => __('Shipping details', 'woocommerce'),
            'Additional information' => __('Additional information', 'woocommerce'),
            'Your order' => __('Your order', 'woocommerce'),
            'Order notes' => __('Order notes', 'woocommerce'),
            'Create an account?' => __('Create an account?', 'woocommerce'),
            'Ship to a different address?' => __('Ship to a different address?', 'woocommerce'),
            
            // Product strings
            'Add to cart' => __('Add to cart', 'woocommerce'),
            'Read more' => __('Read more', 'woocommerce'),
            'Select options' => __('Select options', 'woocommerce'),
            'Choose an option' => __('Choose an option', 'woocommerce'),
            'Clear' => __('Clear', 'woocommerce'),
            
            // Account strings
            'My account' => __('My account', 'woocommerce'),
            'Dashboard' => __('Dashboard', 'woocommerce'),
            'Orders' => __('Orders', 'woocommerce'),
            'Downloads' => __('Downloads', 'woocommerce'),
            'Addresses' => __('Addresses', 'woocommerce'),
            'Account details' => __('Account details', 'woocommerce'),
            'Logout' => __('Logout', 'woocommerce'),
            'Login' => __('Login', 'woocommerce'),
            'Register' => __('Register', 'woocommerce'),
            
            // Status strings
            'In stock' => __('In stock', 'woocommerce'),
            'Out of stock' => __('Out of stock', 'woocommerce'),
            'On backorder' => __('On backorder', 'woocommerce'),
            'Available on backorder' => __('Available on backorder', 'woocommerce'),
            
            // Price strings
            'Free!' => __('Free!', 'woocommerce'),
            'Sale!' => __('Sale!', 'woocommerce'),
            'From:' => __('From:', 'woocommerce'),
            
            // Shipping strings
            'Free shipping' => __('Free shipping', 'woocommerce'),
            'Local pickup' => __('Local pickup', 'woocommerce'),
            'Flat rate' => __('Flat rate', 'woocommerce'),
            
            // Error messages
            'Please enter a valid email address.' => __('Please enter a valid email address.', 'woocommerce'),
            'This field is required.' => __('This field is required.', 'woocommerce'),
            'Please select a payment method.' => __('Please select a payment method.', 'woocommerce'),
        );
        
        foreach ($widget_strings as $name => $string) {
            pll_register_string($name, $string, 'WooCommerce Widgets');
        }
    }
    
    /**
     * Translate add to cart button text
     */
    public function translate_add_to_cart_text($text, $product) {
        if (function_exists('pll__')) {
            switch ($product->get_type()) {
                case 'variable':
                    return pll__('Select options') ?: $text;
                case 'grouped':
                    return pll__('View products') ?: $text;
                case 'external':
                    return pll__('Buy product') ?: $text;
                default:
                    return pll__('Add to cart') ?: $text;
            }
        }
        return $text;
    }
    
    /**
     * Translate single product add to cart text
     */
    public function translate_single_add_to_cart_text($text, $product) {
        if (function_exists('pll__')) {
            return pll__('Add to cart') ?: $text;
        }
        return $text;
    }
    
    /**
     * Translate WooCommerce strings
     */
    public function translate_woocommerce_strings($translated, $text, $domain) {
        if ($domain !== 'woocommerce' || !function_exists('pll__')) {
            return $translated;
        }
        
        $polylang_translation = pll__($text);
        return $polylang_translation ?: $translated;
    }
    
    /**
     * Translate WooCommerce plural strings
     */
    public function translate_woocommerce_plural_strings($translated, $single, $plural, $number, $domain) {
        if ($domain !== 'woocommerce' || !function_exists('pll__')) {
            return $translated;
        }
        
        $text_to_translate = ($number === 1) ? $single : $plural;
        $polylang_translation = pll__($text_to_translate);
        
        return $polylang_translation ?: $translated;
    }
    
    /**
     * Translate script data for AJAX requests
     */
    public function translate_script_data($params, $handle) {
        if (!function_exists('pll__')) {
            return $params;
        }
        
        // Translate common AJAX messages
        if (isset($params['i18n_view_cart'])) {
            $params['i18n_view_cart'] = pll__('View cart') ?: $params['i18n_view_cart'];
        }
        
        if (isset($params['i18n_unavailable_text'])) {
            $params['i18n_unavailable_text'] = pll__('Sorry, this product is unavailable. Please choose a different combination.') ?: $params['i18n_unavailable_text'];
        }
        
        return $params;
    }
    
    /**
     * Translate add to cart message
     */
    public function translate_add_to_cart_message_html($message, $products, $show_qty) {
        if (!function_exists('pll__')) {
            return $message;
        }
        
        // Extract and translate the message text
        $dom = new DOMDocument();
        @$dom->loadHTML($message);
        $xpath = new DOMXPath($dom);
        $textNodes = $xpath->query('//text()');
        
        foreach ($textNodes as $textNode) {
            $original_text = trim($textNode->nodeValue);
            if (!empty($original_text)) {
                $translated_text = pll__($original_text);
                if ($translated_text) {
                    $textNode->nodeValue = $translated_text;
                }
            }
        }
        
        return $dom->saveHTML();
    }
    
    /**
     * Translate remove link
     */
    public function translate_remove_link($link, $cart_item_key) {
        if (function_exists('pll__')) {
            $translated_title = pll__('Remove this item') ?: 'Remove this item';
            $link = str_replace('Remove this item', $translated_title, $link);
        }
        return $link;
    }
    
    /**
     * Translate checkout fields
     */
    public function translate_checkout_fields($fields) {
        if (!function_exists('pll__')) {
            return $fields;
        }
        
        // Translate field labels
        foreach ($fields as $fieldset_key => $fieldset) {
            foreach ($fieldset as $key => $field) {
                if (isset($field['label'])) {
                    $translated_label = pll__($field['label']);
                    if ($translated_label) {
                        $fields[$fieldset_key][$key]['label'] = $translated_label;
                    }
                }
                
                if (isset($field['placeholder'])) {
                    $translated_placeholder = pll__($field['placeholder']);
                    if ($translated_placeholder) {
                        $fields[$fieldset_key][$key]['placeholder'] = $translated_placeholder;
                    }
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * Translate cart fragments for AJAX updates
     */
    public function translate_cart_fragments($fragments) {
        if (!function_exists('pll__')) {
            return $fragments;
        }
        
        // Translate cart count and other dynamic content
        foreach ($fragments as $selector => $content) {
            // Translate common patterns in cart fragments
            $content = preg_replace_callback('/(\d+)\s+items?/', function($matches) {
                $count = $matches[1];
                $item_text = ($count == 1) ? pll__('item') : pll__('items');
                return $count . ' ' . ($item_text ?: $matches[0]);
            }, $content);
            
            $fragments[$selector] = $content;
        }
        
        return $fragments;
    }
}
