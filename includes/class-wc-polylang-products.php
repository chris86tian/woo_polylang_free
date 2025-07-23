<?php
/**
 * Product translations - MINIMAL VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Products {
    
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
    
    public function init() {
        // Add product post type to Polylang
        add_filter('pll_get_post_types', array($this, 'add_post_types'));
        
        // Add product taxonomies to Polylang
        add_filter('pll_get_taxonomies', array($this, 'add_taxonomies'));
    }
    
    /**
     * Add post types to Polylang
     */
    public function add_post_types($post_types) {
        $post_types['product'] = 'product';
        return $post_types;
    }
    
    /**
     * Add taxonomies to Polylang
     */
    public function add_taxonomies($taxonomies) {
        $taxonomies['product_cat'] = 'product_cat';
        $taxonomies['product_tag'] = 'product_tag';
        return $taxonomies;
    }
}
