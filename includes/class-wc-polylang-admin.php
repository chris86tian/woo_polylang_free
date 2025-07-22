<?php
/**
 * Admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (!is_admin()) {
            return;
        }
        
        try {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'init_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            
            // Initialize shop configuration
            WC_Polylang_Shop_Config::get_instance();
            
            wc_polylang_debug_log('Admin class initialized');
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in admin constructor: ' . $e->getMessage());
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Polylang Integration', 'wc-polylang-integration'),
            __('Polylang Integration', 'wc-polylang-integration'),
            'manage_woocommerce',
            'wc-polylang-integration',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('wc_polylang_settings', 'wc_polylang_enable_product_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_enable_category_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_enable_widget_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_enable_email_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_enable_seo_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_enable_custom_fields_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_default_language');
        register_setting('wc_polylang_settings', 'wc_polylang_seo_canonical_urls');
        register_setting('wc_polylang_settings', 'wc_polylang_seo_hreflang_tags');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wc-polylang-integration') === false) {
            return;
        }
        
        wp_enqueue_style(
            'wc-polylang-admin',
            WC_POLYLANG_INTEGRATION_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WC_POLYLANG_INTEGRATION_VERSION
        );
        
        wp_enqueue_script(
            'wc-polylang-admin',
            WC_POLYLANG_INTEGRATION_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WC_POLYLANG_INTEGRATION_VERSION,
            true
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $settings = wc_polylang_get_settings();
        $stats = wc_polylang_get_translation_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('WooCommerce Polylang Integration', 'wc-polylang-integration'); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('Dieses Plugin integriert WooCommerce vollständig mit Polylang für eine umfassende Mehrsprachigkeit.', 'wc-polylang-integration'); ?></p>
            </div>
            
            <!-- Quick Setup Card -->
            <div class="wc-polylang-quick-setup">
                <h2><?php _e('🚀 Schnelleinrichtung', 'wc-polylang-integration'); ?></h2>
                <p><?php _e('Richten Sie Ihre mehrsprachigen Shop-Seiten mit einem Klick ein:', 'wc-polylang-integration'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-polylang-shop-config'); ?>" class="button button-primary button-large">
                    <?php _e('🛍️ Shop-Seiten konfigurieren', 'wc-polylang-integration'); ?>
                </a>
            </div>
            
            <div class="wc-polylang-stats">
                <h2><?php _e('Übersetzungsstatistiken', 'wc-polylang-integration'); ?></h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <h3><?php _e('Produkte', 'wc-polylang-integration'); ?></h3>
                        <p><?php printf(__('%d von %d übersetzt (%d%%)', 'wc-polylang-integration'), $stats['products']['translated'], $stats['products']['total'], $stats['products']['percentage']); ?></p>
                    </div>
                    <div class="stat-item">
                        <h3><?php _e('Kategorien', 'wc-polylang-integration'); ?></h3>
                        <p><?php printf(__('%d von %d übersetzt (%d%%)', 'wc-polylang-integration'), $stats['categories']['translated'], $stats['categories']['total'], $stats['categories']['percentage']); ?></p>
                    </div>
                </div>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('wc_polylang_settings', 'wc_polylang_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Produktübersetzungen aktivieren', 'wc-polylang-integration'); ?></th>
                        <td>
                            <input type="checkbox" name="enable_product_translation" value="yes" <?php checked($settings['enable_product_translation'], 'yes'); ?> />
                            <p class="description"><?php _e('Aktiviert die Übersetzung von Produkten, Kategorien und Attributen.', 'wc-polylang-integration'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Widget-Übersetzungen aktivieren', 'wc-polylang-integration'); ?></th>
                        <td>
                            <input type="checkbox" name="enable_widget_translation" value="yes" <?php checked($settings['enable_widget_translation'], 'yes'); ?> />
                            <p class="description"><?php _e('Übersetzt WooCommerce-Widgets und Buttons.', 'wc-polylang-integration'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('E-Mail-Übersetzungen aktivieren', 'wc-polylang-integration'); ?></th>
                        <td>
                            <input type="checkbox" name="enable_email_translation" value="yes" <?php checked($settings['enable_email_translation'], 'yes'); ?> />
                            <p class="description"><?php _e('Sendet E-Mails in der Sprache des Kunden.', 'wc-polylang-integration'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('SEO-Optimierung aktivieren', 'wc-polylang-integration'); ?></th>
                        <td>
                            <input type="checkbox" name="enable_seo_translation" value="yes" <?php checked($settings['enable_seo_translation'], 'yes'); ?> />
                            <p class="description"><?php _e('Fügt hreflang-Tags und kanonische URLs hinzu.', 'wc-polylang-integration'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Einstellungen speichern', 'wc-polylang-integration')); ?>
            </form>
        </div>
        
        <style>
        .wc-polylang-quick-setup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .wc-polylang-quick-setup h2 {
            color: white;
            margin-top: 0;
        }
        .wc-polylang-quick-setup .button {
            background: white;
            color: #667eea;
            border: none;
            font-weight: bold;
            margin-top: 15px;
        }
        .wc-polylang-quick-setup .button:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .wc-polylang-stats {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .stat-item h3 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        .stat-item p {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #0073aa;
        }
        </style>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['wc_polylang_nonce'], 'wc_polylang_settings')) {
            return;
        }
        
        $settings = array(
            'enable_product_translation' => isset($_POST['enable_product_translation']) ? 'yes' : 'no',
            'enable_widget_translation' => isset($_POST['enable_widget_translation']) ? 'yes' : 'no',
            'enable_email_translation' => isset($_POST['enable_email_translation']) ? 'yes' : 'no',
            'enable_seo_translation' => isset($_POST['enable_seo_translation']) ? 'yes' : 'no',
        );
        
        wc_polylang_update_settings($settings);
        
        echo '<div class="notice notice-success"><p>' . __('Einstellungen gespeichert.', 'wc-polylang-integration') . '</p></div>';
    }
}
