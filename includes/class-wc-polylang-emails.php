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
        
        try {
            $this->init_hooks();
            wc_polylang_debug_log('Emails class initialized');
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in emails constructor: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Set language for emails
        add_action('woocommerce_email_before_order_table', array($this, 'set_email_language'), 1, 4);
        add_action('woocommerce_email_after_order_table', array($this, 'restore_language'), 999, 4);
        
        // Translate email subjects and content
        add_filter('woocommerce_email_subject_new_order', array($this, 'translate_email_subject'), 10, 2);
        add_filter('woocommerce_email_subject_customer_processing_order', array($this, 'translate_email_subject'), 10, 2);
        add_filter('woocommerce_email_subject_customer_completed_order', array($this, 'translate_email_subject'), 10, 2);
    }
    
    /**
     * Set email language based on order
     */
    public function set_email_language($order, $sent_to_admin, $plain_text, $email) {
        if (!$order || !function_exists('pll_set_language')) {
            return;
        }
        
        $order_language = wc_polylang_get_order_language($order->get_id());
        if ($order_language) {
            // Store current language
            $this->original_language = function_exists('pll_current_language') ? pll_current_language() : null;
            
            // Set order language
            pll_set_language($order_language);
        }
    }
    
    /**
     * Restore original language after email
     */
    public function restore_language($order, $sent_to_admin, $plain_text, $email) {
        if (isset($this->original_language) && function_exists('pll_set_language')) {
            pll_set_language($this->original_language);
            unset($this->original_language);
        }
    }
    
    /**
     * Translate email subjects
     */
    public function translate_email_subject($subject, $order) {
        if (!function_exists('pll__')) {
            return $subject;
        }
        
        // Register and translate common email subjects
        $subjects = array(
            'Your {site_title} order receipt from {order_date}' => 'Ihre {site_title} Bestellbestätigung vom {order_date}',
            'Your {site_title} order from {order_date} is complete' => 'Ihre {site_title} Bestellung vom {order_date} ist abgeschlossen',
            '[{site_title}] New customer order ({order_number}) - {order_date}' => '[{site_title}] Neue Kundenbestellung ({order_number}) - {order_date}',
        );
        
        foreach ($subjects as $original => $translation) {
            if (strpos($subject, str_replace(array('{site_title}', '{order_date}', '{order_number}'), '', $original)) !== false) {
                $translated = pll__($original);
                if ($translated && $translated !== $original) {
                    return $translated;
                }
            }
        }
        
        return $subject;
    }
}
