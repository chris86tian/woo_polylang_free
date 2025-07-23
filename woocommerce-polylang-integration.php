<?php
/**
 * Plugin Name: WooCommerce Polylang Integration
 * Plugin URI: https://example.com/woocommerce-polylang-integration
 * Description: Vollständige WooCommerce-Mehrsprachigkeit mit Polylang-Integration und Elementor Pro Support. Macht alle WooCommerce-Inhalte übersetzbar und bietet SEO-optimierte Sprachversionen.
 * Version: 1.1.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: wc-polylang-integration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_POLYLANG_INTEGRATION_VERSION', '1.1.0');
define('WC_POLYLANG_INTEGRATION_PLUGIN_FILE', __FILE__);
define('WC_POLYLANG_INTEGRATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_POLYLANG_INTEGRATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_POLYLANG_INTEGRATION_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Debug logging function
 */
function wc_polylang_debug_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = '[WC Polylang Integration] ' . $message;
        if ($data !== null) {
            $log_message .= ' | Data: ' . print_r($data, true);
        }
        error_log($log_message);
    }
}

/**
 * Main plugin class
 */
class WC_Polylang_Integration {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Plugin components
     */
    private $components = array();
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Hook into plugins_loaded with error handling
        add_action('plugins_loaded', array($this, 'init'), 20);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check dependencies first
        if (!$this->check_dependencies()) {
            return;
        }
        
        // Load text domain
        $this->load_textdomain();
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
        
        // Add hooks
        $this->add_hooks();
    }
    
    /**
     * Load text domain
     */
    private function load_textdomain() {
        load_plugin_textdomain(
            'wc-polylang-integration', 
            false, 
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
        $missing_plugins = array();
        
        // Check WooCommerce
        if (!class_exists('WooCommerce')) {
            $missing_plugins[] = 'WooCommerce';
        }
        
        // Check Polylang (check for both free and pro versions)
        if (!function_exists('pll_languages_list') && !class_exists('Polylang')) {
            $missing_plugins[] = 'Polylang';
        }
        
        if (!empty($missing_plugins)) {
            add_action('admin_notices', function() use ($missing_plugins) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    __('WooCommerce Polylang Integration benötigt folgende Plugins: %s', 'wc-polylang-integration'),
                    implode(', ', $missing_plugins)
                );
                echo '</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * Include required files
     */
    private function includes() {
        $files = array(
            'includes/functions.php',
            'includes/class-wc-polylang-admin.php',
            'includes/class-wc-polylang-shop-config.php',
            'includes/class-wc-polylang-products.php',
            'includes/class-wc-polylang-widgets.php',
            'includes/class-wc-polylang-emails.php',
            'includes/class-wc-polylang-seo.php',
            'includes/class-wc-polylang-custom-fields.php',
            'includes/class-wc-polylang-hooks.php',
            'includes/class-wc-polylang-elementor.php'
        );
        
        foreach ($files as $file) {
            $file_path = WC_POLYLANG_INTEGRATION_PLUGIN_DIR . $file;
            
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize admin
        if (is_admin() && class_exists('WC_Polylang_Admin')) {
            $this->components['admin'] = WC_Polylang_Admin::get_instance();
        }
        
        // Initialize frontend components
        $frontend_components = array(
            'products' => 'WC_Polylang_Products',
            'widgets' => 'WC_Polylang_Widgets',
            'emails' => 'WC_Polylang_Emails',
            'seo' => 'WC_Polylang_SEO',
            'custom_fields' => 'WC_Polylang_Custom_Fields',
            'hooks' => 'WC_Polylang_Hooks',
            'elementor' => 'WC_Polylang_Elementor'
        );
        
        foreach ($frontend_components as $key => $class_name) {
            if (class_exists($class_name)) {
                $this->components[$key] = $class_name::get_instance();
            }
        }
    }
    
    /**
     * Add plugin hooks
     */
    private function add_hooks() {
        // Add settings link
        add_filter('plugin_action_links_' . WC_POLYLANG_INTEGRATION_PLUGIN_BASENAME, array($this, 'add_settings_link'));
        
        // HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
        
        // Elementor Pro compatibility
        add_action('elementor/init', array($this, 'declare_elementor_compatibility'));
    }
    
    /**
     * Add settings link to plugin page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-polylang-integration') . '">' . __('Einstellungen', 'wc-polylang-integration') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }
    
    /**
     * Declare Elementor Pro compatibility
     */
    public function declare_elementor_compatibility() {
        // Elementor Pro compatibility will be handled by the Elementor component
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check dependencies on activation
        if (!$this->check_dependencies()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('WooCommerce Polylang Integration kann nicht aktiviert werden. Bitte installieren Sie WooCommerce und Polylang.', 'wc-polylang-integration'));
            return;
        }
        
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for translation mappings
        $table_name = $wpdb->prefix . 'wc_polylang_translations';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            object_id bigint(20) NOT NULL,
            object_type varchar(50) NOT NULL,
            language varchar(10) NOT NULL,
            translation_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY object_id (object_id),
            KEY object_type (object_type),
            KEY language (language),
            KEY translation_id (translation_id),
            UNIQUE KEY unique_translation (object_id, object_type, language)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'enable_product_translation' => 'yes',
            'enable_category_translation' => 'yes',
            'enable_widget_translation' => 'yes',
            'enable_email_translation' => 'yes',
            'enable_seo_translation' => 'yes',
            'enable_custom_fields_translation' => 'yes',
            'default_language' => 'de',
            'seo_canonical_urls' => 'yes',
            'seo_hreflang_tags' => 'yes',
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option('wc_polylang_' . $option) === false) {
                update_option('wc_polylang_' . $option, $value);
            }
        }
    }
    
    /**
     * Get component instance
     */
    public function get_component($component) {
        return isset($this->components[$component]) ? $this->components[$component] : null;
    }
}

/**
 * Initialize plugin
 */
function wc_polylang_integration() {
    return WC_Polylang_Integration::get_instance();
}

// Start the plugin
wc_polylang_integration();
