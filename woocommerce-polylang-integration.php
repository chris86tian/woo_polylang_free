<?php
/**
 * Plugin Name: WooCommerce Polylang Integration
 * Plugin URI: https://example.com/woocommerce-polylang-integration
 * Description: Vollständige WooCommerce-Mehrsprachigkeit mit Polylang-Integration. Macht alle WooCommerce-Inhalte übersetzbar und bietet SEO-optimierte Sprachversionen.
 * Version: 1.0.1
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

// Enable error logging for debugging
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

// Define plugin constants
define('WC_POLYLANG_INTEGRATION_VERSION', '1.0.1');
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
        wc_polylang_debug_log('Plugin constructor called');
        
        // Use try-catch to prevent fatal errors
        try {
            add_action('plugins_loaded', array($this, 'init'), 20); // Later priority to ensure dependencies are loaded
            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in constructor: ' . $e->getMessage());
            add_action('admin_notices', array($this, 'show_error_notice'));
        }
    }
    
    /**
     * Show error notice
     */
    public function show_error_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('WooCommerce Polylang Integration: Ein Fehler ist aufgetreten. Bitte prüfen Sie die Debug-Logs.', 'wc-polylang-integration');
        echo '</p></div>';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        wc_polylang_debug_log('Plugin init called');
        
        try {
            // Check dependencies first
            if (!$this->check_dependencies()) {
                wc_polylang_debug_log('Dependencies check failed');
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
            
            wc_polylang_debug_log('Plugin initialized successfully');
            
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in init: ' . $e->getMessage());
            add_action('admin_notices', array($this, 'show_error_notice'));
        }
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
            wc_polylang_debug_log('WooCommerce not found');
        }
        
        // Check Polylang (check for both free and pro versions)
        if (!function_exists('pll_languages_list') && !class_exists('Polylang')) {
            $missing_plugins[] = 'Polylang';
            wc_polylang_debug_log('Polylang not found');
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
        
        wc_polylang_debug_log('All dependencies found');
        return true;
    }
    
    /**
     * Include required files
     */
    private function includes() {
        wc_polylang_debug_log('Including files');
        
        $files = array(
            'includes/functions.php',
            'includes/class-wc-polylang-admin.php',
            'includes/class-wc-polylang-products.php',
            'includes/class-wc-polylang-widgets.php',
            'includes/class-wc-polylang-emails.php',
            'includes/class-wc-polylang-seo.php',
            'includes/class-wc-polylang-custom-fields.php',
            'includes/class-wc-polylang-hooks.php'
        );
        
        foreach ($files as $file) {
            $file_path = WC_POLYLANG_INTEGRATION_PLUGIN_DIR . $file;
            
            if (file_exists($file_path)) {
                try {
                    require_once $file_path;
                    wc_polylang_debug_log('Included file: ' . $file);
                } catch (Exception $e) {
                    wc_polylang_debug_log('Error including file ' . $file . ': ' . $e->getMessage());
                    throw $e;
                }
            } else {
                wc_polylang_debug_log('File not found: ' . $file_path);
                throw new Exception('Required file not found: ' . $file);
            }
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        wc_polylang_debug_log('Initializing components');
        
        try {
            // Initialize admin
            if (is_admin() && class_exists('WC_Polylang_Admin')) {
                $this->components['admin'] = WC_Polylang_Admin::get_instance();
                wc_polylang_debug_log('Admin component initialized');
            }
            
            // Initialize frontend components
            $frontend_components = array(
                'products' => 'WC_Polylang_Products',
                'widgets' => 'WC_Polylang_Widgets',
                'emails' => 'WC_Polylang_Emails',
                'seo' => 'WC_Polylang_SEO',
                'custom_fields' => 'WC_Polylang_Custom_Fields',
                'hooks' => 'WC_Polylang_Hooks'
            );
            
            foreach ($frontend_components as $key => $class_name) {
                if (class_exists($class_name)) {
                    $this->components[$key] = $class_name::get_instance();
                    wc_polylang_debug_log('Component initialized: ' . $key);
                } else {
                    wc_polylang_debug_log('Component class not found: ' . $class_name);
                }
            }
            
        } catch (Exception $e) {
            wc_polylang_debug_log('Error initializing components: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Add plugin hooks
     */
    private function add_hooks() {
        wc_polylang_debug_log('Adding hooks');
        
        try {
            // Add settings link
            add_filter('plugin_action_links_' . WC_POLYLANG_INTEGRATION_PLUGIN_BASENAME, array($this, 'add_settings_link'));
            
            // HPOS compatibility
            add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
            
            wc_polylang_debug_log('Hooks added successfully');
            
        } catch (Exception $e) {
            wc_polylang_debug_log('Error adding hooks: ' . $e->getMessage());
            throw $e;
        }
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
     * Plugin activation
     */
    public function activate() {
        wc_polylang_debug_log('Plugin activation started');
        
        try {
            // Check dependencies on activation
            if (!$this->check_dependencies()) {
                wc_polylang_debug_log('Activation failed: missing dependencies');
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
            
            wc_polylang_debug_log('Plugin activated successfully');
            
        } catch (Exception $e) {
            wc_polylang_debug_log('Error during activation: ' . $e->getMessage());
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('Aktivierungsfehler: ' . $e->getMessage());
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        wc_polylang_debug_log('Plugin deactivated');
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        try {
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
            
            wc_polylang_debug_log('Database tables created');
            
        } catch (Exception $e) {
            wc_polylang_debug_log('Error creating tables: ' . $e->getMessage());
            throw $e;
        }
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
        
        wc_polylang_debug_log('Default options set');
    }
    
    /**
     * Get component instance
     */
    public function get_component($component) {
        return isset($this->components[$component]) ? $this->components[$component] : null;
    }
}

/**
 * Initialize plugin with error handling
 */
function wc_polylang_integration() {
    try {
        return WC_Polylang_Integration::get_instance();
    } catch (Exception $e) {
        wc_polylang_debug_log('Fatal error initializing plugin: ' . $e->getMessage());
        
        // Show admin notice instead of fatal error
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>WooCommerce Polylang Integration Fehler:</strong> ' . esc_html($e->getMessage());
            echo '</p></div>';
        });
        
        return null;
    }
}

// Start the plugin
add_action('plugins_loaded', 'wc_polylang_integration', 5);
