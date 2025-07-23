<?php
/**
 * Product translations - MIT DEBUG
 */

if (!defined('ABSPATH')) {
    exit;
}

// Debug-Funktion für Products-Klasse
function wc_polylang_products_debug_log($message, $level = 'INFO') {
    if (class_exists('WC_Polylang_Debug')) {
        WC_Polylang_Debug::log("PRODUCTS CLASS: " . $message, $level);
    }
}

wc_polylang_products_debug_log("class-wc-polylang-products.php wird geladen...");

class WC_Polylang_Products {
    
    private static $instance = null;
    
    public static function get_instance() {
        wc_polylang_products_debug_log("get_instance() aufgerufen");
        if (null === self::$instance) {
            wc_polylang_products_debug_log("Erstelle neue Products-Instanz");
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        wc_polylang_products_debug_log("Products Konstruktor gestartet");
        
        try {
            add_action('init', array($this, 'init'));
            wc_polylang_products_debug_log("Products init-Hook registriert");
            wc_polylang_products_debug_log("Products class erfolgreich initialisiert");
        } catch (Exception $e) {
            wc_polylang_products_debug_log("Fehler im Products-Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    public function init() {
        wc_polylang_products_debug_log("Products init() aufgerufen");
        
        try {
            // Add product post type to Polylang
            add_filter('pll_get_post_types', array($this, 'add_post_types'));
            
            // Add product taxonomies to Polylang
            add_filter('pll_get_taxonomies', array($this, 'add_taxonomies'));
            
            wc_polylang_products_debug_log("Products Filter erfolgreich registriert");
        } catch (Exception $e) {
            wc_polylang_products_debug_log("Fehler in Products init(): " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Add post types to Polylang
     */
    public function add_post_types($post_types) {
        wc_polylang_products_debug_log("add_post_types() aufgerufen");
        $post_types['product'] = 'product';
        wc_polylang_products_debug_log("Product post type zu Polylang hinzugefügt");
        return $post_types;
    }
    
    /**
     * Add taxonomies to Polylang
     */
    public function add_taxonomies($taxonomies) {
        wc_polylang_products_debug_log("add_taxonomies() aufgerufen");
        $taxonomies['product_cat'] = 'product_cat';
        $taxonomies['product_tag'] = 'product_tag';
        wc_polylang_products_debug_log("Product taxonomies zu Polylang hinzugefügt");
        return $taxonomies;
    }
}

wc_polylang_products_debug_log("class-wc-polylang-products.php erfolgreich geladen");
