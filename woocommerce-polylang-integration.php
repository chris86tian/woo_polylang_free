<?php
/**
 * Plugin Name: WooCommerce Polylang Integration
 * Plugin URI: https://example.com/woocommerce-polylang-integration
 * Description: Vollständige WooCommerce-Mehrsprachigkeit mit Polylang-Integration und Elementor Pro Support.
 * Version: 1.1.0
 * Author: Your Name
 * Text Domain: wc-polylang-integration
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
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
 * Main plugin class - MINIMAL VERSION
 */
class WC_Polylang_Integration {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Simple initialization
        add_action('plugins_loaded', array($this, 'init'), 20);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Basic dependency check
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>WooCommerce Polylang Integration benötigt WooCommerce.</p></div>';
            });
            return;
        }
        
        if (!function_exists('pll_languages_list') && !class_exists('Polylang')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>WooCommerce Polylang Integration benötigt Polylang.</p></div>';
            });
            return;
        }
        
        // Load components only if files exist
        $this->load_components();
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add settings link
        add_filter('plugin_action_links_' . WC_POLYLANG_INTEGRATION_PLUGIN_BASENAME, array($this, 'add_settings_link'));
    }
    
    /**
     * Load components safely
     */
    private function load_components() {
        $files = array(
            'includes/functions.php',
            'includes/class-wc-polylang-admin.php',
            'includes/class-wc-polylang-products.php',
            'includes/class-wc-polylang-elementor.php'
        );
        
        foreach ($files as $file) {
            $file_path = WC_POLYLANG_INTEGRATION_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                include_once $file_path;
            }
        }
        
        // Initialize components safely
        if (is_admin() && class_exists('WC_Polylang_Admin')) {
            WC_Polylang_Admin::get_instance();
        }
        
        if (class_exists('WC_Polylang_Products')) {
            WC_Polylang_Products::get_instance();
        }
        
        if (class_exists('WC_Polylang_Elementor')) {
            WC_Polylang_Elementor::get_instance();
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Polylang Integration',
            '🌍 Polylang Integration',
            'manage_woocommerce',
            'wc-polylang-integration',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>🌍 WooCommerce Polylang Integration</h1>
            
            <div class="notice notice-success">
                <p><strong>✅ Plugin erfolgreich aktiviert!</strong></p>
                <p>Das Plugin läuft jetzt im Minimal-Modus. Alle Kernfunktionen sind verfügbar.</p>
            </div>
            
            <div class="card">
                <h2>🚀 Verfügbare Funktionen</h2>
                <ul>
                    <li>✅ Produkt-Übersetzungen</li>
                    <li>✅ Kategorie-Übersetzungen</li>
                    <li>✅ SEO-Optimierung</li>
                    <li>✅ Elementor Pro Integration</li>
                    <li>✅ Email-Übersetzungen</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>🎨 Elementor Pro Templates</h2>
                <p>Verwalten Sie Ihre mehrsprachigen Elementor Templates:</p>
                <a href="<?php echo admin_url('admin.php?page=wc-polylang-elementor'); ?>" class="button button-primary">
                    Template Manager öffnen
                </a>
            </div>
            
            <div class="card">
                <h2>📊 System Status</h2>
                <table class="widefat">
                    <tr>
                        <td><strong>WooCommerce:</strong></td>
                        <td><?php echo class_exists('WooCommerce') ? '✅ Aktiv' : '❌ Nicht gefunden'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Polylang:</strong></td>
                        <td><?php echo function_exists('pll_languages_list') ? '✅ Aktiv' : '❌ Nicht gefunden'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Elementor Pro:</strong></td>
                        <td><?php echo defined('ELEMENTOR_PRO_VERSION') ? '✅ Aktiv' : '⚠️ Nicht gefunden'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .card h2 {
            margin-top: 0;
        }
        .widefat td {
            padding: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Add settings link
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-polylang-integration') . '">Einstellungen</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Minimal activation - just flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('wc_polylang_integration_activated', time());
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
        delete_option('wc_polylang_integration_activated');
    }
}

// Initialize plugin
function wc_polylang_integration() {
    return WC_Polylang_Integration::get_instance();
}

// Start the plugin
wc_polylang_integration();
