<?php
/**
 * Plugin Name: WooCommerce Polylang Integration
 * Plugin URI: https://www.lipalife.de/woocommerce-polylang-integration
 * Description: Vollständige WooCommerce-Mehrsprachigkeit mit Polylang-Integration und Elementor Pro Support.
 * Version: 1.2.0
 * Author: LipaLIFE
 * Author URI: https://www.lipalife.de
 * Text Domain: wc-polylang-integration
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @package WC_Polylang_Integration
 * @author LipaLIFE
 * @link https://www.lipalife.de
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_POLYLANG_INTEGRATION_VERSION', '1.2.0');
define('WC_POLYLANG_INTEGRATION_PLUGIN_FILE', __FILE__);
define('WC_POLYLANG_INTEGRATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_POLYLANG_INTEGRATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_POLYLANG_INTEGRATION_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * HPOS COMPATIBILITY DECLARATION
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS)
 */
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('orders_cache', __FILE__, true);
    }
});

/**
 * OPTIMIERTES DEBUG SYSTEM - KONFIGURIERBAR & WENIGER VERBOSE
 */
class WC_Polylang_Debug {
    
    private static $log_file = null;
    private static $debug_enabled = null;
    private static $debug_level = null;
    
    // Debug-Level Konstanten
    const LEVEL_OFF = 0;        // Kein Debug
    const LEVEL_ERROR = 1;      // Nur Fehler
    const LEVEL_WARNING = 2;    // Fehler + Warnungen
    const LEVEL_INFO = 3;       // Fehler + Warnungen + wichtige Infos
    const LEVEL_DEBUG = 4;      // Alles (wie vorher)
    
    public static function init() {
        self::$log_file = WP_CONTENT_DIR . '/wc-polylang-debug.log';
        
        // Lade Debug-Einstellungen
        self::$debug_enabled = get_option('wc_polylang_debug_enabled', false);
        self::$debug_level = get_option('wc_polylang_debug_level', self::LEVEL_ERROR);
        
        // Nur initialisieren wenn Debug aktiviert ist
        if (!self::$debug_enabled) {
            return;
        }
        
        // Stelle sicher, dass die Log-Datei existiert und beschreibbar ist
        if (!file_exists(self::$log_file)) {
            @file_put_contents(self::$log_file, "=== WC Polylang Integration Debug Log ===\n");
        }
        
        // Setze Error Handler nur bei höherem Debug-Level
        if (self::$debug_level >= self::LEVEL_WARNING) {
            set_error_handler(array(__CLASS__, 'error_handler'));
            register_shutdown_function(array(__CLASS__, 'shutdown_handler'));
        }
    }
    
    public static function log($message, $level = 'INFO') {
        // Prüfe ob Debug aktiviert ist
        if (!self::is_debug_enabled()) {
            return;
        }
        
        // Prüfe Debug-Level
        if (!self::should_log($level)) {
            return;
        }
        
        if (!self::$log_file) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}\n";
        
        // Schreibe in unsere eigene Log-Datei
        @file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Zusätzlich in WordPress Debug Log (falls aktiviert und Level hoch genug)
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log') && self::$debug_level >= self::LEVEL_DEBUG) {
            error_log("WC Polylang: {$message}");
        }
    }
    
    /**
     * Prüfe ob Debug aktiviert ist
     */
    private static function is_debug_enabled() {
        if (self::$debug_enabled === null) {
            self::$debug_enabled = get_option('wc_polylang_debug_enabled', false);
        }
        return self::$debug_enabled;
    }
    
    /**
     * Prüfe ob Level geloggt werden soll
     */
    private static function should_log($level) {
        if (self::$debug_level === null) {
            self::$debug_level = get_option('wc_polylang_debug_level', self::LEVEL_ERROR);
        }
        
        $level_priority = self::get_level_priority($level);
        return $level_priority <= self::$debug_level;
    }
    
    /**
     * Hole Level-Priorität
     */
    private static function get_level_priority($level) {
        switch (strtoupper($level)) {
            case 'FATAL':
            case 'ERROR':
                return self::LEVEL_ERROR;
            case 'WARNING':
                return self::LEVEL_WARNING;
            case 'INFO':
            case 'SUCCESS':
                return self::LEVEL_INFO;
            case 'DEBUG':
            default:
                return self::LEVEL_DEBUG;
        }
    }
    
    public static function error_handler($errno, $errstr, $errfile, $errline) {
        if (!self::is_debug_enabled() || self::$debug_level < self::LEVEL_WARNING) {
            return false;
        }
        
        $error_types = array(
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED'
        );
        
        $error_type = isset($error_types[$errno]) ? $error_types[$errno] : 'UNKNOWN';
        
        // Nur Fehler aus unserem Plugin loggen
        if (strpos($errfile, 'woocommerce-polylang-integration') !== false) {
            self::log("PHP {$error_type}: {$errstr} in {$errfile} on line {$errline}", 'ERROR');
        }
        
        return false; // Lass PHP den Fehler normal behandeln
    }
    
    public static function shutdown_handler() {
        if (!self::is_debug_enabled() || self::$debug_level < self::LEVEL_ERROR) {
            return;
        }
        
        $error = error_get_last();
        if ($error && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
            // Nur Fehler aus unserem Plugin loggen
            if (strpos($error['file'], 'woocommerce-polylang-integration') !== false) {
                self::log("FATAL ERROR: {$error['message']} in {$error['file']} on line {$error['line']}", 'FATAL');
            }
        }
    }
    
    public static function get_log_content() {
        if (!file_exists(self::$log_file)) {
            return "Keine Debug-Logs gefunden.";
        }
        
        return file_get_contents(self::$log_file);
    }
    
    public static function clear_log() {
        if (file_exists(self::$log_file)) {
            @unlink(self::$log_file);
        }
        if (self::is_debug_enabled()) {
            self::init();
        }
    }
    
    /**
     * Hole Debug-Einstellungen für Admin
     */
    public static function get_debug_settings() {
        return array(
            'enabled' => get_option('wc_polylang_debug_enabled', false),
            'level' => get_option('wc_polylang_debug_level', self::LEVEL_ERROR),
            'file_size' => file_exists(self::$log_file) ? filesize(self::$log_file) : 0,
            'file_path' => self::$log_file
        );
    }
    
    /**
     * Speichere Debug-Einstellungen
     */
    public static function save_debug_settings($enabled, $level) {
        update_option('wc_polylang_debug_enabled', $enabled);
        update_option('wc_polylang_debug_level', $level);
        
        // Aktualisiere interne Variablen
        self::$debug_enabled = $enabled;
        self::$debug_level = $level;
        
        // Re-initialisiere wenn aktiviert
        if ($enabled) {
            self::init();
        }
    }
}

// Initialisiere Debug System nur wenn nötig
WC_Polylang_Debug::init();

// Nur loggen wenn Debug aktiviert und Level hoch genug
if (get_option('wc_polylang_debug_enabled', false) && get_option('wc_polylang_debug_level', 1) >= 3) {
    WC_Polylang_Debug::log("Plugin wird geladen... (HPOS-kompatible Version von LipaLIFE)", 'INFO');
}

/**
 * Main plugin class - MIT OPTIMIERTEM DEBUG
 */
class WC_Polylang_Integration {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            WC_Polylang_Debug::log("Plugin-Instanz wird erstellt", 'INFO');
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        WC_Polylang_Debug::log("Plugin Konstruktor gestartet", 'DEBUG');
        
        try {
            // Simple initialization
            add_action('plugins_loaded', array($this, 'init'), 20);
            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));
            
            WC_Polylang_Debug::log("Plugin-Hooks registriert", 'DEBUG');
        } catch (Exception $e) {
            WC_Polylang_Debug::log("Fehler im Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        WC_Polylang_Debug::log("Plugin wird initialisiert", 'INFO');
        
        try {
            // Check HPOS compatibility
            $this->check_hpos_compatibility();
            
            // Basic dependency check
            if (!class_exists('WooCommerce')) {
                WC_Polylang_Debug::log("WooCommerce nicht gefunden", 'ERROR');
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>WooCommerce Polylang Integration benötigt WooCommerce.</p></div>';
                });
                return;
            }
            
            if (!function_exists('pll_languages_list') && !class_exists('Polylang')) {
                WC_Polylang_Debug::log("Polylang nicht gefunden", 'ERROR');
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>WooCommerce Polylang Integration benötigt Polylang.</p></div>';
                });
                return;
            }
            
            // Load components only if files exist
            $this->load_components();
            
            // Add settings link
            add_filter('plugin_action_links_' . WC_POLYLANG_INTEGRATION_PLUGIN_BASENAME, array($this, 'add_settings_link'));
            
            WC_Polylang_Debug::log("Plugin erfolgreich initialisiert", 'SUCCESS');
            
        } catch (Exception $e) {
            WC_Polylang_Debug::log("KRITISCHER FEHLER in init(): " . $e->getMessage(), 'FATAL');
        }
    }
    
    /**
     * Check HPOS compatibility
     */
    private function check_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            $hpos_enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
            WC_Polylang_Debug::log("HPOS Status: " . ($hpos_enabled ? 'Aktiviert' : 'Deaktiviert'), 'INFO');
        }
    }
    
    /**
     * Load components safely
     */
    private function load_components() {
        WC_Polylang_Debug::log("Lade Plugin-Komponenten", 'INFO');
        
        $files = array(
            'includes/functions.php',
            'includes/class-wc-polylang-admin.php',
            'includes/class-wc-polylang-products.php',
            'includes/class-wc-polylang-categories.php',
            'includes/class-wc-polylang-bilingual-categories.php',
            'includes/class-wc-polylang-elementor.php'
        );
        
        foreach ($files as $file) {
            $file_path = WC_POLYLANG_INTEGRATION_PLUGIN_DIR . $file;
            
            if (file_exists($file_path)) {
                try {
                    include_once $file_path;
                    WC_Polylang_Debug::log("Datei geladen: {$file}", 'DEBUG');
                } catch (Exception $e) {
                    WC_Polylang_Debug::log("Fehler beim Laden von {$file}: " . $e->getMessage(), 'ERROR');
                }
            } else {
                WC_Polylang_Debug::log("Datei nicht gefunden: {$file}", 'WARNING');
            }
        }
        
        // Initialize components safely
        try {
            if (is_admin() && class_exists('WC_Polylang_Admin')) {
                WC_Polylang_Admin::get_instance();
                WC_Polylang_Debug::log("Admin-Komponente initialisiert", 'DEBUG');
            }
            
            if (class_exists('WC_Polylang_Products')) {
                WC_Polylang_Products::get_instance();
                WC_Polylang_Debug::log("Products-Komponente initialisiert", 'DEBUG');
            }
            
            if (class_exists('WC_Polylang_Categories')) {
                WC_Polylang_Categories::get_instance();
                WC_Polylang_Debug::log("Categories-Komponente initialisiert", 'DEBUG');
            }
            
            if (class_exists('WC_Polylang_Bilingual_Categories')) {
                WC_Polylang_Bilingual_Categories::get_instance();
                WC_Polylang_Debug::log("Bilingual Categories-Komponente initialisiert", 'DEBUG');
            }
            
            if (class_exists('WC_Polylang_Elementor')) {
                WC_Polylang_Elementor::get_instance();
                WC_Polylang_Debug::log("Elementor-Komponente initialisiert", 'DEBUG');
            }
            
            WC_Polylang_Debug::log("Alle Komponenten erfolgreich initialisiert", 'SUCCESS');
            
        } catch (Exception $e) {
            WC_Polylang_Debug::log("KRITISCHER FEHLER beim Initialisieren der Komponenten: " . $e->getMessage(), 'FATAL');
        } catch (Error $e) {
            WC_Polylang_Debug::log("FATAL ERROR beim Initialisieren der Komponenten: " . $e->getMessage(), 'FATAL');
        }
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
        WC_Polylang_Debug::log("Plugin wird aktiviert", 'INFO');
        
        try {
            // Minimal activation - just flush rewrite rules
            flush_rewrite_rules();
            
            // Set activation flag
            update_option('wc_polylang_integration_activated', time());
            
            WC_Polylang_Debug::log("Plugin erfolgreich aktiviert", 'SUCCESS');
        } catch (Exception $e) {
            WC_Polylang_Debug::log("Fehler bei Aktivierung: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        WC_Polylang_Debug::log("Plugin wird deaktiviert", 'INFO');
        
        try {
            flush_rewrite_rules();
            delete_option('wc_polylang_integration_activated');
            
            WC_Polylang_Debug::log("Plugin erfolgreich deaktiviert", 'SUCCESS');
        } catch (Exception $e) {
            WC_Polylang_Debug::log("Fehler bei Deaktivierung: " . $e->getMessage(), 'ERROR');
        }
    }
}

// AJAX Handler für Debug-Einstellungen
add_action('wp_ajax_wc_polylang_save_debug_settings', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_debug')) {
        wp_die('Nonce verification failed');
    }
    
    $enabled = isset($_POST['enabled']) && $_POST['enabled'] === 'true';
    $level = intval($_POST['level']);
    
    WC_Polylang_Debug::save_debug_settings($enabled, $level);
    
    wp_send_json_success('Debug-Einstellungen gespeichert');
});

// AJAX Handler für Debug-Log löschen
add_action('wp_ajax_wc_polylang_clear_debug_log', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_debug')) {
        wp_die('Nonce verification failed');
    }
    
    WC_Polylang_Debug::clear_log();
    wp_send_json_success('Debug-Log gelöscht');
});

// Initialize plugin
function wc_polylang_integration() {
    return WC_Polylang_Integration::get_instance();
}

// Start the plugin
try {
    WC_Polylang_Debug::log("Plugin wird gestartet", 'DEBUG');
    wc_polylang_integration();
    WC_Polylang_Debug::log("Plugin erfolgreich gestartet", 'SUCCESS');
} catch (Exception $e) {
    WC_Polylang_Debug::log("KRITISCHER FEHLER beim Plugin-Start: " . $e->getMessage(), 'FATAL');
} catch (Error $e) {
    WC_Polylang_Debug::log("FATAL ERROR beim Plugin-Start: " . $e->getMessage(), 'FATAL');
}
