<?php
/**
 * Email translation functionality
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
        if (get_option('wc_polylang_enable_email_translation') !== 'yes') {
            return;
        }
        
        add_action('init', array($this, 'init'));
        add_filter('woocommerce_email_get_option', array($this, 'translate_email_options'), 10, 4);
        add_action('woocommerce_email_before_order_table', array($this, 'set_email_language'), 10, 4);
    }
    
    /**
     * Initialize email translation
     */
    public function init() {
        // Register email strings
        if (function_exists('pll_register_string')) {
            add_action('wp_loaded', array($this, 'register_email_strings'));
        }
        
        // Handle email language switching
        add_filter('woocommerce_email_recipient_customer_processing_order', array($this, 'set_customer_email_language'), 10, 2);
        add_filter('woocommerce_email_recipient_customer_completed_order', array($this, 'set_customer_email_language'), 10, 2);
        add_filter('woocommerce_email_recipient_customer_invoice', array($this, 'set_customer_email_language'), 10, 2);
        add_filter('woocommerce_email_recipient_customer_note', array($this, 'set_customer_email_language'), 10, 2);
        
        // Translate email subjects and headings
        add_filter('woocommerce_email_subject_customer_processing_order', array($this, 'translate_email_subject'), 10, 2);
        add_filter('woocommerce_email_subject_customer_completed_order', array($this, 'translate_email_subject'), 10, 2);
        add_filter('woocommerce_email_subject_customer_invoice', array($this, 'translate_email_subject'), 10, 2);
        add_filter('woocommerce_email_subject_customer_note', array($this, 'translate_email_subject'), 10, 2);
        
        add_filter('woocommerce_email_heading_customer_processing_order', array($this, 'translate_email_heading'), 10, 2);
        add_filter('woocommerce_email_heading_customer_completed_order', array($this, 'translate_email_heading'), 10, 2);
        add_filter('woocommerce_email_heading_customer_invoice', array($this, 'translate_email_heading'), 10, 2);
        add_filter('woocommerce_email_heading_customer_note', array($this, 'translate_email_heading'), 10, 2);
    }
    
    /**
     * Register email strings with Polylang
     */
    public function register_email_strings() {
        $email_strings = array(
            // Order processing email
            'Your order has been received!' => __('Your order has been received!', 'woocommerce'),
            'Thank you for your order' => __('Thank you for your order', 'woocommerce'),
            'Your order is on-hold until we confirm that payment has been received.' => __('Your order is on-hold until we confirm that payment has been received.', 'woocommerce'),
            
            // Order completed email
            'Your order is complete' => __('Your order is complete', 'woocommerce'),
            'Thanks for shopping with us.' => __('Thanks for shopping with us.', 'woocommerce'),
            
            // Invoice email
            'Invoice for order' => __('Invoice for order', 'woocommerce'),
            'An invoice has been created for you to pay for this order.' => __('An invoice has been created for you to pay for this order.', 'woocommerce'),
            
            // Customer note email
            'Note added to your order' => __('Note added to your order', 'woocommerce'),
            'A note has been added to your order' => __('A note has been added to your order', 'woocommerce'),
            
            // Common email strings
            'Order details' => __('Order details', 'woocommerce'),
            'Order:' => __('Order:', 'woocommerce'),
            'Date:' => __('Date:', 'woocommerce'),
            'Email:' => __('Email:', 'woocommerce'),
            'Tel:' => __('Tel:', 'woocommerce'),
            'Payment method:' => __('Payment method:', 'woocommerce'),
            'Billing address' => __('Billing address', 'woocommerce'),
            'Shipping address' => __('Shipping address', 'woocommerce'),
            'Product' => __('Product', 'woocommerce'),
            'Quantity' => __('Quantity', 'woocommerce'),
            'Price' => __('Price', 'woocommerce'),
            'Subtotal:' => __('Subtotal:', 'woocommerce'),
            'Shipping:' => __('Shipping:', 'woocommerce'),
            'Payment method:' => __('Payment method:', 'woocommerce'),
            'Total:' => __('Total:', 'woocommerce'),
            
            // Footer strings
            'Thanks for reading.' => __('Thanks for reading.', 'woocommerce'),
            'This email was sent from' => __('This email was sent from', 'woocommerce'),
        );
        
        foreach ($email_strings as $name => $string) {
            pll_register_string($name, $string, 'WooCommerce Emails');
        }
        
        // Register custom email templates
        $this->register_custom_email_templates();
    }
    
    /**
     * Register custom email templates
     */
    private function register_custom_email_templates() {
        $emails = WC()->mailer()->get_emails();
        
        foreach ($emails as $email) {
            // Register subject
            if (!empty($email->subject)) {
                pll_register_string(
                    'Email Subject: ' . $email->id,
                    $email->subject,
                    'WooCommerce Email Templates'
                );
            }
            
            // Register heading
            if (!empty($email->heading)) {
                pll_register_string(
                    'Email Heading: ' . $email->id,
                    $email->heading,
                    'WooCommerce Email Templates'
                );
            }
            
            // Register additional text
            if (!empty($email->additional_content)) {
                pll_register_string(
                    'Email Additional Content: ' . $email->id,
                    $email->additional_content,
                    'WooCommerce Email Templates'
                );
            }
        }
    }
    
    /**
     * Translate email options
     */
    public function translate_email_options($value, $email, $option_name, $default) {
        if (!function_exists('pll__')) {
            return $value;
        }
        
        switch ($option_name) {
            case 'subject':
                $string_name = 'Email Subject: ' . $email->id;
                break;
            case 'heading':
                $string_name = 'Email Heading: ' . $email->id;
                break;
            case 'additional_content':
                $string_name = 'Email Additional Content: ' . $email->id;
                break;
            default:
                return $value;
        }
        
        $translated = pll__($string_name);
        return $translated ?: $value;
    }
    
    /**
     * Set customer email language based on order
     */
    public function set_customer_email_language($recipient, $order) {
        if (!function_exists('pll_set_language') || !$order) {
            return $recipient;
        }
        
        // Get order language
        $order_language = $this->get_order_language($order);
        
        if ($order_language) {
            // Switch to order language for email
            pll_set_language($order_language);
        }
        
        return $recipient;
    }
    
    /**
     * Get order language
     */
    private function get_order_language($order) {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }
        
        if (!$order) {
            return false;
        }
        
        // Try to get language from order meta
        $language = $order->get_meta('_order_language');
        
        if (!$language && function_exists('pll_get_post_language')) {
            // Fallback: get language from first product in order
            $items = $order->get_items();
            foreach ($items as $item) {
                $product_id = $item->get_product_id();
                if ($product_id) {
                    $language = pll_get_post_language($product_id);
                    if ($language) {
                        break;
                    }
                }
            }
        }
        
        return $language ?: pll_default_language();
    }
    
    /**
     * Set email language before sending
     */
    public function set_email_language($order, $sent_to_admin, $plain_text, $email) {
        if ($sent_to_admin || !function_exists('pll_set_language')) {
            return;
        }
        
        $order_language = $this->get_order_language($order);
        
        if ($order_language) {
            pll_set_language($order_language);
            
            // Store original language to restore later
            if (!isset($GLOBALS['wc_polylang_original_language'])) {
                $GLOBALS['wc_polylang_original_language'] = pll_current_language();
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
        
        // Set language context
        $order_language = $this->get_order_language($order);
        if ($order_language) {
            pll_set_language($order_language);
        }
        
        // Translate common subject patterns
        $translations = array(
            'Your {site_title} order has been received!' => pll__('Your {site_title} order has been received!'),
            'Your {site_title} order is complete' => pll__('Your {site_title} order is complete'),
            'Invoice for order {order_number}' => pll__('Invoice for order {order_number}'),
            'Note added to your {site_title} order from {order_date}' => pll__('Note added to your {site_title} order from {order_date}'),
        );
        
        foreach ($translations as $original => $translated) {
            if ($translated && strpos($subject, str_replace(array('{site_title}', '{order_number}', '{order_date}'), '', $original)) !== false) {
                $subject = str_replace($original, $translated, $subject);
                break;
            }
        }
        
        return $subject;
    }
    
    /**
     * Translate email heading
     */
    public function translate_email_heading($heading, $order) {
        if (!function_exists('pll__')) {
            return $heading;
        }
        
        // Set language context
        $order_language = $this->get_order_language($order);
        if ($order_language) {
            pll_set_language($order_language);
        }
        
        // Translate common heading patterns
        $translations = array(
            'Thank you for your order' => pll__('Thank you for your order'),
            'Your order is complete' => pll__('Your order is complete'),
            'Invoice for order {order_number}' => pll__('Invoice for order {order_number}'),
            'A note has been added to your order' => pll__('A note has been added to your order'),
        );
        
        foreach ($translations as $original => $translated) {
            if ($translated && strpos($heading, str_replace('{order_number}', '', $original)) !== false) {
                $heading = str_replace($original, $translated, $heading);
                break;
            }
        }
        
        return $heading;
    }
}
