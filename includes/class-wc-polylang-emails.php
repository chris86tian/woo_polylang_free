<?php
/**
 * Email translations
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Emails {
    
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
        if (wc_polylang_get_settings()['enable_email_translation'] !== 'yes') {
            return;
        }
        
        try {
            // Hook into WooCommerce email system
            add_action('woocommerce_email_before_order_table', array($this, 'set_email_language'), 1, 4);
            add_action('woocommerce_email_after_order_table', array($this, 'restore_language'), 999, 4);
            
            // Handle email subjects and headings
            add_filter('woocommerce_email_subject_new_order', array($this, 'translate_email_subject'), 10, 2);
            add_filter('woocommerce_email_subject_customer_processing_order', array($this, 'translate_email_subject'), 10, 2);
            add_filter('woocommerce_email_subject_customer_completed_order', array($this, 'translate_email_subject'), 10, 2);
            add_filter('woocommerce_email_subject_customer_invoice', array($this, 'translate_email_subject'), 10, 2);
            add_filter('woocommerce_email_subject_customer_note', array($this, 'translate_email_subject'), 10, 2);
            add_filter('woocommerce_email_subject_customer_reset_password', array($this, 'translate_email_subject'), 10, 2);
            add_filter('woocommerce_email_subject_customer_new_account', array($this, 'translate_email_subject'), 10, 2);
            
            // Handle email headings
            add_filter('woocommerce_email_heading_new_order', array($this, 'translate_email_heading'), 10, 2);
            add_filter('woocommerce_email_heading_customer_processing_order', array($this, 'translate_email_heading'), 10, 2);
            add_filter('woocommerce_email_heading_customer_completed_order', array($this, 'translate_email_heading'), 10, 2);
            add_filter('woocommerce_email_heading_customer_invoice', array($this, 'translate_email_heading'), 10, 2);
            add_filter('woocommerce_email_heading_customer_note', array($this, 'translate_email_heading'), 10, 2);
            add_filter('woocommerce_email_heading_customer_reset_password', array($this, 'translate_email_heading'), 10, 2);
            add_filter('woocommerce_email_heading_customer_new_account', array($this, 'translate_email_heading'), 10, 2);
            
            // Handle email content
            add_filter('woocommerce_email_additional_content_new_order', array($this, 'translate_email_content'), 10, 3);
            add_filter('woocommerce_email_additional_content_customer_processing_order', array($this, 'translate_email_content'), 10, 3);
            add_filter('woocommerce_email_additional_content_customer_completed_order', array($this, 'translate_email_content'), 10, 3);
            add_filter('woocommerce_email_additional_content_customer_invoice', array($this, 'translate_email_content'), 10, 3);
            add_filter('woocommerce_email_additional_content_customer_note', array($this, 'translate_email_content'), 10, 3);
            add_filter('woocommerce_email_additional_content_customer_reset_password', array($this, 'translate_email_content'), 10, 3);
            add_filter('woocommerce_email_additional_content_customer_new_account', array($this, 'translate_email_content'), 10, 3);
            
            // Register email strings
            add_action('init', array($this, 'register_email_strings'), 25);
            
            // Handle order language detection
            add_action('woocommerce_checkout_order_processed', array($this, 'save_order_language'), 10, 1);
            
            wc_polylang_debug_log('Email translations initialized');
            
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in emails init: ' . $e->getMessage());
        }
    }
    
    /**
     * Set email language based on order
     */
    public function set_email_language($order, $sent_to_admin, $plain_text, $email) {
        if (!function_exists('pll_set_language')) {
            return;
        }
        
        // Get order language
        $order_language = $this->get_order_language($order);
        
        if ($order_language) {
            // Store current language to restore later
            $this->original_language = function_exists('pll_current_language') ? pll_current_language() : null;
            
            // Set language for email
            pll_set_language($order_language);
            
            // Switch locale
            switch_to_locale(get_locale());
            
            wc_polylang_debug_log('Email language set to: ' . $order_language);
        }
    }
    
    /**
     * Restore original language after email
     */
    public function restore_language($order, $sent_to_admin, $plain_text, $email) {
        if (!function_exists('pll_set_language')) {
            return;
        }
        
        if (isset($this->original_language) && $this->original_language) {
            pll_set_language($this->original_language);
            restore_current_locale();
            
            wc_polylang_debug_log('Email language restored to: ' . $this->original_language);
        }
    }
    
    /**
     * Get order language
     */
    private function get_order_language($order) {
        if (!$order) {
            return false;
        }
        
        // Try to get from order meta
        $order_language = $order->get_meta('_order_language');
        
        if (!$order_language) {
            // Try to get from first product
            $items = $order->get_items();
            foreach ($items as $item) {
                $product_id = $item->get_product_id();
                if ($product_id && function_exists('pll_get_post_language')) {
                    $order_language = pll_get_post_language($product_id);
                    if ($order_language) {
                        // Save for future use
                        $order->update_meta_data('_order_language', $order_language);
                        $order->save();
                        break;
                    }
                }
            }
        }
        
        // Fallback to current language or default
        if (!$order_language) {
            $order_language = function_exists('pll_current_language') ? pll_current_language() : 'de';
        }
        
        return $order_language;
    }
    
    /**
     * Save order language during checkout
     */
    public function save_order_language($order_id) {
        if (!function_exists('pll_current_language')) {
            return;
        }
        
        $current_language = pll_current_language();
        if ($current_language) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_meta_data('_order_language', $current_language);
                $order->save();
                
                wc_polylang_debug_log('Order language saved: ' . $current_language . ' for order ' . $order_id);
            }
        }
    }
    
    /**
     * Translate email subject
     */
    public function translate_email_subject($subject, $order) {
        if (!function_exists('pll__')) {
            return $subject;
        }
        
        // Register subject for translation
        $string_name = 'Email Subject: ' . $subject;
        
        if (function_exists('pll_register_string')) {
            pll_register_string($string_name, $subject, 'WooCommerce Emails');
        }
        
        $translated_subject = pll__($string_name);
        return $translated_subject ?: $subject;
    }
    
    /**
     * Translate email heading
     */
    public function translate_email_heading($heading, $order) {
        if (!function_exists('pll__')) {
            return $heading;
        }
        
        // Register heading for translation
        $string_name = 'Email Heading: ' . $heading;
        
        if (function_exists('pll_register_string')) {
            pll_register_string($string_name, $heading, 'WooCommerce Emails');
        }
        
        $translated_heading = pll__($string_name);
        return $translated_heading ?: $heading;
    }
    
    /**
     * Translate email content
     */
    public function translate_email_content($content, $order, $email) {
        if (!function_exists('pll__') || empty($content)) {
            return $content;
        }
        
        // Register content for translation
        $string_name = 'Email Content: ' . substr($content, 0, 50) . '...';
        
        if (function_exists('pll_register_string')) {
            pll_register_string($string_name, $content, 'WooCommerce Emails');
        }
        
        $translated_content = pll__($string_name);
        return $translated_content ?: $content;
    }
    
    /**
     * Register email strings for translation
     */
    public function register_email_strings() {
        if (!function_exists('pll_register_string')) {
            return;
        }
        
        // Get all WooCommerce email settings
        $email_settings = $this->get_email_settings();
        
        foreach ($email_settings as $email_id => $settings) {
            $group = 'WooCommerce Emails - ' . ucfirst(str_replace('_', ' ', $email_id));
            
            // Register subjects
            if (!empty($settings['subject'])) {
                pll_register_string('Email Subject: ' . $settings['subject'], $settings['subject'], $group);
            }
            
            // Register headings
            if (!empty($settings['heading'])) {
                pll_register_string('Email Heading: ' . $settings['heading'], $settings['heading'], $group);
            }
            
            // Register additional content
            if (!empty($settings['additional_content'])) {
                pll_register_string('Email Content: ' . substr($settings['additional_content'], 0, 50) . '...', $settings['additional_content'], $group);
            }
        }
        
        // Register common email strings
        $common_strings = array(
            'Order details' => __('Order details', 'woocommerce'),
            'Order #' => __('Order #', 'woocommerce'),
            'Date:' => __('Date:', 'woocommerce'),
            'Email:' => __('Email:', 'woocommerce'),
            'Phone:' => __('Phone:', 'woocommerce'),
            'Payment method:' => __('Payment method:', 'woocommerce'),
            'Billing address' => __('Billing address', 'woocommerce'),
            'Shipping address' => __('Shipping address', 'woocommerce'),
            'Product' => __('Product', 'woocommerce'),
            'Quantity' => __('Quantity', 'woocommerce'),
            'Price' => __('Price', 'woocommerce'),
            'Subtotal:' => __('Subtotal:', 'woocommerce'),
            'Shipping:' => __('Shipping:', 'woocommerce'),
            'Tax:' => __('Tax:', 'woocommerce'),
            'Total:' => __('Total:', 'woocommerce'),
            'Thanks for shopping with us!' => __('Thanks for shopping with us!', 'woocommerce'),
            'Your order has been received' => __('Your order has been received', 'woocommerce'),
        );
        
        foreach ($common_strings as $name => $string) {
            pll_register_string($name, $string, 'WooCommerce Emails - Common');
        }
    }
    
    /**
     * Get email settings from WooCommerce
     */
    private function get_email_settings() {
        $settings = array();
        
        // Get WooCommerce email instances
        $mailer = WC()->mailer();
        $emails = $mailer->get_emails();
        
        foreach ($emails as $email_id => $email) {
            $settings[$email_id] = array(
                'subject' => $email->get_subject(),
                'heading' => $email->get_heading(),
                'additional_content' => method_exists($email, 'get_additional_content') ? $email->get_additional_content() : ''
            );
        }
        
        return $settings;
    }
    
    /**
     * Get translated email template
     */
    public function get_translated_email_template($template, $template_name, $email_id) {
        if (!function_exists('pll_current_language')) {
            return $template;
        }
        
        $current_language = pll_current_language();
        
        // Look for language-specific template
        $language_template = str_replace('.php', '-' . $current_language . '.php', $template);
        
        if (file_exists($language_template)) {
            return $language_template;
        }
        
        return $template;
    }
    
    /**
     * Handle email template loading
     */
    public function load_email_template($template, $template_name, $args, $template_path, $default_path) {
        // Check for language-specific email templates
        return $this->get_translated_email_template($template, $template_name, '');
    }
    
    /**
     * Get order items in correct language
     */
    public function get_translated_order_items($order) {
        if (!function_exists('pll_get_post')) {
            return $order->get_items();
        }
        
        $order_language = $this->get_order_language($order);
        $items = $order->get_items();
        
        foreach ($items as $item_id => $item) {
            $product_id = $item->get_product_id();
            if ($product_id) {
                $translated_product_id = pll_get_post($product_id, $order_language);
                if ($translated_product_id && $translated_product_id !== $product_id) {
                    $translated_product = wc_get_product($translated_product_id);
                    if ($translated_product) {
                        // Update item name with translated product name
                        $item->set_name($translated_product->get_name());
                    }
                }
            }
        }
        
        return $items;
    }
    
    /**
     * Translate order status
     */
    public function translate_order_status($status) {
        if (!function_exists('pll__')) {
            return $status;
        }
        
        $status_translations = array(
            'pending' => __('Pending payment', 'woocommerce'),
            'processing' => __('Processing', 'woocommerce'),
            'on-hold' => __('On hold', 'woocommerce'),
            'completed' => __('Completed', 'woocommerce'),
            'cancelled' => __('Cancelled', 'woocommerce'),
            'refunded' => __('Refunded', 'woocommerce'),
            'failed' => __('Failed', 'woocommerce'),
        );
        
        if (isset($status_translations[$status])) {
            $translated_status = pll__($status_translations[$status]);
            return $translated_status ?: $status_translations[$status];
        }
        
        return $status;
    }
}
