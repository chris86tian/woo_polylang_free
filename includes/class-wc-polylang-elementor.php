<?php
/**
 * Elementor Pro Integration - MIT DEBUG
 */

if (!defined('ABSPATH')) {
    exit;
}

// Debug-Funktion für Elementor-Klasse
function wc_polylang_elementor_debug_log($message, $level = 'INFO') {
    if (class_exists('WC_Polylang_Debug')) {
        WC_Polylang_Debug::log("ELEMENTOR CLASS: " . $message, $level);
    }
}

wc_polylang_elementor_debug_log("class-wc-polylang-elementor.php wird geladen...");

class WC_Polylang_Elementor {
    
    private static $instance = null;
    
    public static function get_instance() {
        wc_polylang_elementor_debug_log("get_instance() aufgerufen");
        if (null === self::$instance) {
            wc_polylang_elementor_debug_log("Erstelle neue Elementor-Instanz");
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        wc_polylang_elementor_debug_log("Elementor Konstruktor gestartet");
        
        try {
            // Only initialize if Elementor Pro is active
            add_action('init', array($this, 'init'));
            wc_polylang_elementor_debug_log("Elementor init-Hook registriert");
            wc_polylang_elementor_debug_log("Elementor class erfolgreich initialisiert");
        } catch (Exception $e) {
            wc_polylang_elementor_debug_log("Fehler im Elementor-Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Initialize
     */
    public function init() {
        wc_polylang_elementor_debug_log("Elementor init() aufgerufen");
        
        try {
            // Check if Elementor Pro is active
            if (!$this->is_elementor_pro_active()) {
                wc_polylang_elementor_debug_log("Elementor Pro nicht aktiv - beende Initialisierung");
                return;
            }
            
            wc_polylang_elementor_debug_log("Elementor Pro ist aktiv - registriere Hooks");
            
            // Add admin menu
            add_action('admin_menu', array($this, 'add_elementor_menu'));
            
            // Add Polylang support for Elementor templates
            add_filter('pll_get_post_types', array($this, 'add_elementor_post_types'));
            
            wc_polylang_elementor_debug_log("Elementor Hooks erfolgreich registriert");
        } catch (Exception $e) {
            wc_polylang_elementor_debug_log("Fehler in Elementor init(): " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Check if Elementor Pro is active
     */
    private function is_elementor_pro_active() {
        $is_active = defined('ELEMENTOR_PRO_VERSION') && class_exists('ElementorPro\Plugin');
        wc_polylang_elementor_debug_log("Elementor Pro aktiv: " . ($is_active ? 'Ja' : 'Nein'));
        return $is_active;
    }
    
    /**
     * Add Elementor post types to Polylang
     */
    public function add_elementor_post_types($post_types) {
        wc_polylang_elementor_debug_log("add_elementor_post_types() aufgerufen");
        $post_types['elementor_library'] = 'elementor_library';
        wc_polylang_elementor_debug_log("Elementor library post type zu Polylang hinzugefügt");
        return $post_types;
    }
    
    /**
     * Add Elementor admin menu
     */
    public function add_elementor_menu() {
        wc_polylang_elementor_debug_log("add_elementor_menu() aufgerufen");
        add_submenu_page(
            'wc-polylang-integration',
            __('Elementor Templates', 'wc-polylang-integration'),
            __('🎨 Elementor Templates', 'wc-polylang-integration'),
            'manage_woocommerce',
            'wc-polylang-elementor',
            array($this, 'elementor_page')
        );
    }
    
    /**
     * Elementor templates page
     */
    public function elementor_page() {
        wc_polylang_elementor_debug_log("elementor_page() aufgerufen");
        
        if (!$this->is_elementor_pro_active()) {
            echo '<div class="wrap">';
            echo '<h1>' . __('Elementor Pro Integration', 'wc-polylang-integration') . '</h1>';
            echo '<div class="notice notice-warning"><p>' . __('Elementor Pro ist nicht installiert oder aktiviert.', 'wc-polylang-integration') . '</p></div>';
            echo '</div>';
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('🎨 Elementor Pro Template Manager', 'wc-polylang-integration'); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('Verwalten Sie Ihre mehrsprachigen Elementor Pro Templates für WooCommerce automatisch.', 'wc-polylang-integration'); ?></p>
            </div>
            
            <div class="card">
                <h2><?php _e('Template-Funktionen', 'wc-polylang-integration'); ?></h2>
                <p><?php _e('Hier können Sie Ihre Elementor Pro Templates für verschiedene Sprachen verwalten.', 'wc-polylang-integration'); ?></p>
                <p><strong><?php _e('Status:', 'wc-polylang-integration'); ?></strong> <?php _e('Elementor Pro Integration aktiv', 'wc-polylang-integration'); ?></p>
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
        </style>
        <?php
    }
}

wc_polylang_elementor_debug_log("class-wc-polylang-elementor.php erfolgreich geladen");
