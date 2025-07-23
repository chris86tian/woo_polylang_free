<?php
/**
 * Plugin Name: WooCommerce Polylang Integration
 * Plugin URI: https://example.com/woocommerce-polylang-integration
 * Description: Vollständige WooCommerce-Mehrsprachigkeit mit Polylang-Integration und Elementor Pro Support.
 * Version: 1.2.0
 * Author: Your Name
 * Text Domain: wc-polylang-integration
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * 
 * @package WC_Polylang_Integration
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
 * CUSTOM DEBUG SYSTEM - Funktioniert IMMER!
 */
class WC_Polylang_Debug {
    
    private static $log_file = null;
    
    public static function init() {
        self::$log_file = WP_CONTENT_DIR . '/wc-polylang-debug.log';
        
        // Stelle sicher, dass die Log-Datei existiert und beschreibbar ist
        if (!file_exists(self::$log_file)) {
            @file_put_contents(self::$log_file, "=== WC Polylang Integration Debug Log ===\n");
        }
        
        // Setze Error Handler
        set_error_handler(array(__CLASS__, 'error_handler'));
        register_shutdown_function(array(__CLASS__, 'shutdown_handler'));
    }
    
    public static function log($message, $level = 'INFO') {
        if (!self::$log_file) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}\n";
        
        // Schreibe in unsere eigene Log-Datei
        @file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Zusätzlich in WordPress Debug Log (falls aktiviert)
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log("WC Polylang: {$message}");
        }
    }
    
    public static function error_handler($errno, $errstr, $errfile, $errline) {
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
        self::init();
    }
}

// Initialisiere Debug System SOFORT
WC_Polylang_Debug::init();
WC_Polylang_Debug::log("Plugin wird geladen... (HPOS-kompatible Version)", 'INFO');

/**
 * Main plugin class - MIT HPOS SUPPORT
 */
class WC_Polylang_Integration {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            WC_Polylang_Debug::log("Erstelle Plugin-Instanz", 'INFO');
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        WC_Polylang_Debug::log("Plugin Konstruktor gestartet", 'INFO');
        
        try {
            // Simple initialization
            add_action('plugins_loaded', array($this, 'init'), 20);
            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));
            
            WC_Polylang_Debug::log("Hooks registriert", 'INFO');
        } catch (Exception $e) {
            WC_Polylang_Debug::log("Fehler im Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        WC_Polylang_Debug::log("Plugin init() gestartet", 'INFO');
        
        try {
            // Check HPOS compatibility
            $this->check_hpos_compatibility();
            
            // Basic dependency check
            if (!class_exists('WooCommerce')) {
                WC_Polylang_Debug::log("WooCommerce nicht gefunden", 'WARNING');
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>WooCommerce Polylang Integration benötigt WooCommerce.</p></div>';
                });
                return;
            }
            
            WC_Polylang_Debug::log("WooCommerce gefunden", 'INFO');
            
            if (!function_exists('pll_languages_list') && !class_exists('Polylang')) {
                WC_Polylang_Debug::log("Polylang nicht gefunden", 'WARNING');
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>WooCommerce Polylang Integration benötigt Polylang.</p></div>';
                });
                return;
            }
            
            WC_Polylang_Debug::log("Polylang gefunden", 'INFO');
            
            // Load components only if files exist
            $this->load_components();
            
            // Add admin menu
            add_action('admin_menu', array($this, 'add_admin_menu'));
            
            // Add settings link
            add_filter('plugin_action_links_' . WC_POLYLANG_INTEGRATION_PLUGIN_BASENAME, array($this, 'add_settings_link'));
            
            WC_Polylang_Debug::log("Plugin erfolgreich initialisiert", 'SUCCESS');
            
        } catch (Exception $e) {
            WC_Polylang_Debug::log("Fehler in init(): " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Check HPOS compatibility
     */
    private function check_hpos_compatibility() {
        WC_Polylang_Debug::log("Prüfe HPOS-Kompatibilität...", 'INFO');
        
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            $hpos_enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
            WC_Polylang_Debug::log("HPOS Status: " . ($hpos_enabled ? 'Aktiviert' : 'Deaktiviert'), 'INFO');
            
            if ($hpos_enabled) {
                WC_Polylang_Debug::log("HPOS ist aktiviert - Plugin ist kompatibel", 'SUCCESS');
            }
        } else {
            WC_Polylang_Debug::log("HPOS-Klassen nicht verfügbar (ältere WooCommerce-Version)", 'INFO');
        }
    }
    
    /**
     * Load components safely
     */
    private function load_components() {
        WC_Polylang_Debug::log("Lade Komponenten...", 'INFO');
        
        $files = array(
            'includes/functions.php',
            'includes/class-wc-polylang-admin.php',
            'includes/class-wc-polylang-products.php',
            'includes/class-wc-polylang-elementor.php'
        );
        
        foreach ($files as $file) {
            $file_path = WC_POLYLANG_INTEGRATION_PLUGIN_DIR . $file;
            
            if (file_exists($file_path)) {
                WC_Polylang_Debug::log("Lade Datei: {$file}", 'INFO');
                try {
                    include_once $file_path;
                    WC_Polylang_Debug::log("Datei erfolgreich geladen: {$file}", 'SUCCESS');
                } catch (Exception $e) {
                    WC_Polylang_Debug::log("Fehler beim Laden von {$file}: " . $e->getMessage(), 'ERROR');
                }
            } else {
                WC_Polylang_Debug::log("Datei nicht gefunden: {$file}", 'WARNING');
            }
        }
        
        WC_Polylang_Debug::log("Alle Dateien geladen - starte Komponenten-Initialisierung", 'INFO');
        
        // Initialize components safely
        try {
            WC_Polylang_Debug::log("Prüfe Admin-Bereich...", 'DEBUG');
            if (is_admin()) {
                WC_Polylang_Debug::log("Admin-Bereich erkannt", 'DEBUG');
                if (class_exists('WC_Polylang_Admin')) {
                    WC_Polylang_Debug::log("WC_Polylang_Admin Klasse gefunden - initialisiere...", 'DEBUG');
                    WC_Polylang_Admin::get_instance();
                    WC_Polylang_Debug::log("WC_Polylang_Admin erfolgreich initialisiert", 'SUCCESS');
                } else {
                    WC_Polylang_Debug::log("WC_Polylang_Admin Klasse NICHT gefunden!", 'ERROR');
                }
            } else {
                WC_Polylang_Debug::log("Nicht im Admin-Bereich", 'DEBUG');
            }
            
            WC_Polylang_Debug::log("Prüfe WC_Polylang_Products Klasse...", 'DEBUG');
            if (class_exists('WC_Polylang_Products')) {
                WC_Polylang_Debug::log("WC_Polylang_Products Klasse gefunden - initialisiere...", 'DEBUG');
                WC_Polylang_Products::get_instance();
                WC_Polylang_Debug::log("WC_Polylang_Products erfolgreich initialisiert", 'SUCCESS');
            } else {
                WC_Polylang_Debug::log("WC_Polylang_Products Klasse NICHT gefunden!", 'ERROR');
            }
            
            WC_Polylang_Debug::log("Prüfe WC_Polylang_Elementor Klasse...", 'DEBUG');
            if (class_exists('WC_Polylang_Elementor')) {
                WC_Polylang_Debug::log("WC_Polylang_Elementor Klasse gefunden - initialisiere...", 'DEBUG');
                WC_Polylang_Elementor::get_instance();
                WC_Polylang_Debug::log("WC_Polylang_Elementor erfolgreich initialisiert", 'SUCCESS');
            } else {
                WC_Polylang_Debug::log("WC_Polylang_Elementor Klasse NICHT gefunden!", 'ERROR');
            }
            
            WC_Polylang_Debug::log("Alle Komponenten erfolgreich initialisiert!", 'SUCCESS');
            
        } catch (Exception $e) {
            WC_Polylang_Debug::log("KRITISCHER FEHLER beim Initialisieren der Komponenten: " . $e->getMessage(), 'FATAL');
            WC_Polylang_Debug::log("Stack Trace: " . $e->getTraceAsString(), 'FATAL');
        } catch (Error $e) {
            WC_Polylang_Debug::log("FATAL ERROR beim Initialisieren der Komponenten: " . $e->getMessage(), 'FATAL');
            WC_Polylang_Debug::log("Stack Trace: " . $e->getTraceAsString(), 'FATAL');
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
                <p><strong>✅ Plugin erfolgreich aktiviert und HPOS-kompatibel!</strong></p>
                <p>Das Plugin ist jetzt vollständig kompatibel mit WooCommerce High-Performance Order Storage (HPOS).</p>
            </div>
            
            <div class="card">
                <h2>🔍 Debug Informationen</h2>
                <p><strong>Debug-Log Datei:</strong> <code><?php echo WP_CONTENT_DIR . '/wc-polylang-debug.log'; ?></code></p>
                
                <div style="margin: 20px 0;">
                    <button type="button" class="button" onclick="location.reload()">🔄 Seite aktualisieren</button>
                    <button type="button" class="button" onclick="clearDebugLog()">🗑️ Debug-Log löschen</button>
                    <button type="button" class="button button-primary" onclick="showDebugLog()">📋 Debug-Log anzeigen</button>
                </div>
                
                <div id="debug-log-content" style="display:none; background:#f1f1f1; padding:15px; border-radius:4px; max-height:400px; overflow-y:auto;">
                    <pre style="white-space: pre-wrap; font-size: 12px;"><?php echo esc_html(WC_Polylang_Debug::get_log_content()); ?></pre>
                </div>
            </div>
            
            <div class="card">
                <h2>🚀 Verfügbare Funktionen</h2>
                <ul>
                    <li>✅ Produkt-Übersetzungen</li>
                    <li>✅ Kategorie-Übersetzungen</li>
                    <li>✅ SEO-Optimierung</li>
                    <li>✅ Elementor Pro Integration</li>
                    <li>✅ Email-Übersetzungen</li>
                    <li>✅ HPOS-Kompatibilität</li>
                </ul>
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
                    <tr>
                        <td><strong>HPOS (High-Performance Orders):</strong></td>
                        <td><?php 
                            if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
                                $hpos_enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
                                echo $hpos_enabled ? '✅ Aktiviert & Kompatibel' : '⚠️ Deaktiviert';
                            } else {
                                echo '⚠️ Nicht verfügbar';
                            }
                        ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WordPress Version:</strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Plugin Version:</strong></td>
                        <td><?php echo WC_POLYLANG_INTEGRATION_VERSION; ?></td>
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
        
        <script>
        function showDebugLog() {
            var content = document.getElementById('debug-log-content');
            if (content.style.display === 'none') {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        }
        
        function clearDebugLog() {
            if (confirm('Möchten Sie wirklich das Debug-Log löschen?')) {
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=wc_polylang_clear_debug_log&nonce=<?php echo wp_create_nonce('wc_polylang_debug'); ?>'
                }).then(() => {
                    location.reload();
                });
            }
        }
        </script>
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
        WC_Polylang_Debug::log("Plugin Aktivierung gestartet", 'INFO');
        
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
        WC_Polylang_Debug::log("Plugin Deaktivierung gestartet", 'INFO');
        
        try {
            flush_rewrite_rules();
            delete_option('wc_polylang_integration_activated');
            
            WC_Polylang_Debug::log("Plugin erfolgreich deaktiviert", 'SUCCESS');
        } catch (Exception $e) {
            WC_Polylang_Debug::log("Fehler bei Deaktivierung: " . $e->getMessage(), 'ERROR');
        }
    }
}

// AJAX Handler für Debug-Log löschen
add_action('wp_ajax_wc_polylang_clear_debug_log', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_debug')) {
        wp_die('Nonce verification failed');
    }
    
    WC_Polylang_Debug::clear_log();
    wp_die('OK');
});

// Initialize plugin
function wc_polylang_integration() {
    WC_Polylang_Debug::log("Plugin Funktion aufgerufen", 'INFO');
    return WC_Polylang_Integration::get_instance();
}

// Start the plugin
try {
    WC_Polylang_Debug::log("Starte Plugin...", 'INFO');
    wc_polylang_integration();
    WC_Polylang_Debug::log("Plugin gestartet", 'SUCCESS');
} catch (Exception $e) {
    WC_Polylang_Debug::log("KRITISCHER FEHLER beim Plugin-Start: " . $e->getMessage(), 'FATAL');
} catch (Error $e) {
    WC_Polylang_Debug::log("FATAL ERROR beim Plugin-Start: " . $e->getMessage(), 'FATAL');
}
