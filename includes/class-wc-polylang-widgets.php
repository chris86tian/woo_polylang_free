<?php
/**
 * Widget translations
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
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        if (wc_polylang_get_settings()['enable_widget_translation'] !== 'yes') {
            return;
        }
        
        try {
            // Register WooCommerce strings for translation
            add_action('init', array($this, 'register_woocommerce_strings'), 20);
            
            // Translate WooCommerce texts
            add_filter('gettext', array($this, 'translate_woocommerce_texts'), 10, 3);
            add_filter('ngettext', array($this, 'translate_woocommerce_plural_texts'), 10, 5);
            
            // Handle cart widget
            add_filter('woocommerce_widget_cart_item_quantity', array($this, 'translate_cart_item_quantity'), 10, 3);
            
            // Handle checkout texts
            add_filter('woocommerce_checkout_fields', array($this, 'translate_checkout_fields'));
            
            // Handle my account texts
            add_filter('woocommerce_account_menu_items', array($this, 'translate_account_menu_items'));
            
            // Handle product buttons
            add_filter('woocommerce_product_add_to_cart_text', array($this, 'translate_add_to_cart_text'), 10, 2);
            
            // Handle shop messages
            add_filter('wc_add_to_cart_message_html', array($this, 'translate_cart_messages'), 10, 3);
            
            // Handle breadcrumbs
            add_filter('woocommerce_breadcrumb_defaults', array($this, 'translate_breadcrumb_defaults'));
            
            // Handle pagination
            add_filter('woocommerce_pagination_args', array($this, 'translate_pagination_args'));
            
            // Handle product tabs
            add_filter('woocommerce_product_tabs', array($this, 'translate_product_tabs'));
            
            // Handle order status texts
            add_filter('wc_order_statuses', array($this, 'translate_order_statuses'));
            
            // Handle shipping methods
            add_filter('woocommerce_package_rates', array($this, 'translate_shipping_methods'));
            
            // Handle payment gateways
            add_filter('woocommerce_gateway_title', array($this, 'translate_payment_gateway_title'), 10, 2);
            add_filter('woocommerce_gateway_description', array($this, 'translate_payment_gateway_description'), 10, 2);
            
            wc_polylang_debug_log('Widget translations initialized');
            
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in widgets init: ' . $e->getMessage());
        }
    }
    
    /**
     * Register WooCommerce strings for translation
     */
    public function register_woocommerce_strings() {
        if (!function_exists('pll_register_string')) {
            return;
        }
        
        // Common WooCommerce strings
        $strings = array(
            // Cart & Checkout
            'Add to cart' => __('Add to cart', 'woocommerce'),
            'View cart' => __('View cart', 'woocommerce'),
            'Checkout' => __('Checkout', 'woocommerce'),
            'Cart' => __('Cart', 'woocommerce'),
            'Your cart is currently empty.' => __('Your cart is currently empty.', 'woocommerce'),
            'Proceed to checkout' => __('Proceed to checkout', 'woocommerce'),
            'Update cart' => __('Update cart', 'woocommerce'),
            'Apply coupon' => __('Apply coupon', 'woocommerce'),
            'Remove this item' => __('Remove this item', 'woocommerce'),
            
            // Product
            'In stock' => __('In stock', 'woocommerce'),
            'Out of stock' => __('Out of stock', 'woocommerce'),
            'On backorder' => __('On backorder', 'woocommerce'),
            'Select options' => __('Select options', 'woocommerce'),
            'Read more' => __('Read more', 'woocommerce'),
            'Sale!' => __('Sale!', 'woocommerce'),
            'Free!' => __('Free!', 'woocommerce'),
            
            // Shop
            'Shop' => __('Shop', 'woocommerce'),
            'Products' => __('Products', 'woocommerce'),
            'Categories' => __('Categories', 'woocommerce'),
            'Tags' => __('Tags', 'woocommerce'),
            'Search products' => __('Search products', 'woocommerce'),
            'No products found' => __('No products found', 'woocommerce'),
            'Showing all %d results' => __('Showing all %d results', 'woocommerce'),
            'Showing the single result' => __('Showing the single result', 'woocommerce'),
            'Default sorting' => __('Default sorting', 'woocommerce'),
            'Sort by popularity' => __('Sort by popularity', 'woocommerce'),
            'Sort by average rating' => __('Sort by average rating', 'woocommerce'),
            'Sort by latest' => __('Sort by latest', 'woocommerce'),
            'Sort by price: low to high' => __('Sort by price: low to high', 'woocommerce'),
            'Sort by price: high to low' => __('Sort by price: high to low', 'woocommerce'),
            
            // My Account
            'My account' => __('My account', 'woocommerce'),
            'Dashboard' => __('Dashboard', 'woocommerce'),
            'Orders' => __('Orders', 'woocommerce'),
            'Downloads' => __('Downloads', 'woocommerce'),
            'Addresses' => __('Addresses', 'woocommerce'),
            'Account details' => __('Account details', 'woocommerce'),
            'Logout' => __('Logout', 'woocommerce'),
            'Login' => __('Login', 'woocommerce'),
            'Register' => __('Register', 'woocommerce'),
            
            // Order Status
            'Pending payment' => __('Pending payment', 'woocommerce'),
            'Processing' => __('Processing', 'woocommerce'),
            'On hold' => __('On hold', 'woocommerce'),
            'Completed' => __('Completed', 'woocommerce'),
            'Cancelled' => __('Cancelled', 'woocommerce'),
            'Refunded' => __('Refunded', 'woocommerce'),
            'Failed' => __('Failed', 'woocommerce'),
            
            // Breadcrumbs
            'Home' => __('Home', 'woocommerce'),
            
            // Pagination
            'Previous' => __('Previous', 'woocommerce'),
            'Next' => __('Next', 'woocommerce'),
            
            // Product Tabs
            'Description' => __('Description', 'woocommerce'),
            'Additional information' => __('Additional information', 'woocommerce'),
            'Reviews' => __('Reviews', 'woocommerce'),
            
            // Messages
            'Product successfully added to your cart.' => __('Product successfully added to your cart.', 'woocommerce'),
            'Continue shopping' => __('Continue shopping', 'woocommerce'),
            
            // Filters
            'Filter by price' => __('Filter by price', 'woocommerce'),
            'Filter' => __('Filter', 'woocommerce'),
            'Clear' => __('Clear', 'woocommerce'),
            'Price' => __('Price', 'woocommerce'),
            
            // Shipping
            'Free shipping' => __('Free shipping', 'woocommerce'),
            'Flat rate' => __('Flat rate', 'woocommerce'),
            'Local pickup' => __('Local pickup', 'woocommerce'),
            
            // Payment
            'Direct bank transfer' => __('Direct bank transfer', 'woocommerce'),
            'Check payments' => __('Check payments', 'woocommerce'),
            'Cash on delivery' => __('Cash on delivery', 'woocommerce'),
            'PayPal' => __('PayPal', 'woocommerce'),
        );
        
        foreach ($strings as $name => $string) {
            pll_register_string($name, $string, 'WooCommerce');
        }
        
        // Register custom strings from theme/plugins
        $this->register_custom_strings();
    }
    
    /**
     * Register custom strings from theme and plugins
     */
    private function register_custom_strings() {
        // Register strings from active theme
        $theme_strings = apply_filters('wc_polylang_theme_strings', array());
        foreach ($theme_strings as $name => $string) {
            pll_register_string($name, $string, 'Theme');
        }
        
        // Register strings from plugins
        $plugin_strings = apply_filters('wc_polylang_plugin_strings', array());
        foreach ($plugin_strings as $name => $string) {
            pll_register_string($name, $string, 'Plugins');
        }
    }
    
    /**
     * Translate WooCommerce texts
     */
    public function translate_woocommerce_texts($translated, $text, $domain) {
        if ($domain !== 'woocommerce' || !function_exists('pll__')) {
            return $translated;
        }
        
        // Try to get translation from Polylang
        $polylang_translation = pll__($text);
        
        return $polylang_translation ?: $translated;
    }
    
    /**
     * Translate WooCommerce plural texts
     */
    public function translate_woocommerce_plural_texts($translated, $single, $plural, $number, $domain) {
        if ($domain !== 'woocommerce' || !function_exists('pll__')) {
            return $translated;
        }
        
        $text_to_translate = ($number == 1) ? $single : $plural;
        $polylang_translation = pll__($text_to_translate);
        
        return $polylang_translation ?: $translated;
    }
    
    /**
     * Translate cart item quantity
     */
    public function translate_cart_item_quantity($quantity_html, $cart_item_key, $cart_item) {
        if (!function_exists('pll__')) {
            return $quantity_html;
        }
        
        // Translate quantity labels if present
        $quantity_html = str_replace('Qty:', pll__('Qty:') ?: 'Qty:', $quantity_html);
        
        return $quantity_html;
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
     * Translate account menu items
     */
    public function translate_account_menu_items($items) {
        if (!function_exists('pll__')) {
            return $items;
        }
        
        $translated_items = array();
        
        foreach ($items as $key => $item) {
            $translated_item = pll__($item);
            $translated_items[$key] = $translated_item ?: $item;
        }
        
        return $translated_items;
    }
    
    /**
     * Translate add to cart button text
     */
    public function translate_add_to_cart_text($text, $product) {
        if (!function_exists('pll__')) {
            return $text;
        }
        
        $translated_text = pll__($text);
        return $translated_text ?: $text;
    }
    
    /**
     * Translate cart messages
     */
    public function translate_cart_messages($message, $products, $show_qty) {
        if (!function_exists('pll__')) {
            return $message;
        }
        
        // Extract and translate text parts
        $patterns = array(
            '/Continue shopping/' => pll__('Continue shopping') ?: 'Continue shopping',
            '/View cart/' => pll__('View cart') ?: 'View cart',
            '/added to your cart/' => pll__('added to your cart') ?: 'added to your cart',
        );
        
        foreach ($patterns as $pattern => $replacement) {
            $message = preg_replace($pattern, $replacement, $message);
        }
        
        return $message;
    }
    
    /**
     * Translate breadcrumb defaults
     */
    public function translate_breadcrumb_defaults($defaults) {
        if (!function_exists('pll__')) {
            return $defaults;
        }
        
        if (isset($defaults['home'])) {
            $translated_home = pll__($defaults['home']);
            if ($translated_home) {
                $defaults['home'] = $translated_home;
            }
        }
        
        return $defaults;
    }
    
    /**
     * Translate pagination arguments
     */
    public function translate_pagination_args($args) {
        if (!function_exists('pll__')) {
            return $args;
        }
        
        if (isset($args['prev_text'])) {
            $translated_prev = pll__($args['prev_text']);
            if ($translated_prev) {
                $args['prev_text'] = $translated_prev;
            }
        }
        
        if (isset($args['next_text'])) {
            $translated_next = pll__($args['next_text']);
            if ($translated_next) {
                $args['next_text'] = $translated_next;
            }
        }
        
        return $args;
    }
    
    /**
     * Translate product tabs
     */
    public function translate_product_tabs($tabs) {
        if (!function_exists('pll__')) {
            return $tabs;
        }
        
        foreach ($tabs as $key => $tab) {
            if (isset($tab['title'])) {
                $translated_title = pll__($tab['title']);
                if ($translated_title) {
                    $tabs[$key]['title'] = $translated_title;
                }
            }
        }
        
        return $tabs;
    }
    
    /**
     * Translate order statuses
     */
    public function translate_order_statuses($statuses) {
        if (!function_exists('pll__')) {
            return $statuses;
        }
        
        $translated_statuses = array();
        
        foreach ($statuses as $key => $status) {
            $translated_status = pll__($status);
            $translated_statuses[$key] = $translated_status ?: $status;
        }
        
        return $translated_statuses;
    }
    
    /**
     * Translate shipping methods
     */
    public function translate_shipping_methods($rates) {
        if (!function_exists('pll__')) {
            return $rates;
        }
        
        foreach ($rates as $rate_id => $rate) {
            $translated_label = pll__($rate->get_label());
            if ($translated_label) {
                $rate->set_label($translated_label);
            }
        }
        
        return $rates;
    }
    
    /**
     * Translate payment gateway title
     */
    public function translate_payment_gateway_title($title, $gateway_id) {
        if (!function_exists('pll__')) {
            return $title;
        }
        
        $translated_title = pll__($title);
        return $translated_title ?: $title;
    }
    
    /**
     * Translate payment gateway description
     */
    public function translate_payment_gateway_description($description, $gateway_id) {
        if (!function_exists('pll__')) {
            return $description;
        }
        
        $translated_description = pll__($description);
        return $translated_description ?: $description;
    }
    
    /**
     * Get translated widget title
     */
    public function get_translated_widget_title($title, $widget_id = '') {
        if (!function_exists('pll__')) {
            return $title;
        }
        
        $string_name = 'Widget Title: ' . $title;
        
        // Register for translation if not already registered
        if (function_exists('pll_register_string')) {
            pll_register_string($string_name, $title, 'Widgets');
        }
        
        $translated_title = pll__($string_name);
        return $translated_title ?: $title;
    }
    
    /**
     * Register widget strings
     */
    public function register_widget_strings($widget_id, $widget_data) {
        if (!function_exists('pll_register_string')) {
            return;
        }
        
        // Register widget title
        if (!empty($widget_data['title'])) {
            pll_register_string('Widget Title: ' . $widget_data['title'], $widget_data['title'], 'Widgets');
        }
        
        // Register other widget strings based on widget type
        $this->register_specific_widget_strings($widget_id, $widget_data);
    }
    
    /**
     * Register specific widget strings
     */
    private function register_specific_widget_strings($widget_id, $widget_data) {
        // WooCommerce Product Categories Widget
        if (strpos($widget_id, 'woocommerce_product_categories') !== false) {
            if (!empty($widget_data['title'])) {
                pll_register_string('Product Categories Widget Title', $widget_data['title'], 'WooCommerce Widgets');
            }
        }
        
        // WooCommerce Product Search Widget
        if (strpos($widget_id, 'woocommerce_product_search') !== false) {
            if (!empty($widget_data['title'])) {
                pll_register_string('Product Search Widget Title', $widget_data['title'], 'WooCommerce Widgets');
            }
        }
        
        // WooCommerce Cart Widget
        if (strpos($widget_id, 'woocommerce_widget_cart') !== false) {
            if (!empty($widget_data['title'])) {
                pll_register_string('Cart Widget Title', $widget_data['title'], 'WooCommerce Widgets');
            }
        }
        
        // Add more widget-specific string registration as needed
    }
}
