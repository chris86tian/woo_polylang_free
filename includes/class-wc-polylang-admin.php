<?php
/**
 * Admin functionality - MIT OPTIMIERTEM DEBUG SYSTEM
 */

if (!defined('ABSPATH')) {
    exit;
}

// Optimierte Debug-Funktion für Admin-Klasse
function wc_polylang_admin_debug_log($message, $level = 'DEBUG') {
    if (class_exists('WC_Polylang_Debug')) {
        WC_Polylang_Debug::log("ADMIN: " . $message, $level);
    }
}

class WC_Polylang_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            wc_polylang_admin_debug_log("Admin-Instanz wird erstellt", 'INFO');
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (!is_admin()) {
            return;
        }
        
        try {
            wc_polylang_admin_debug_log("Admin-Hooks werden registriert", 'DEBUG');
            add_action('admin_menu', array($this, 'add_admin_menu'), 10);
            add_action('admin_init', array($this, 'init_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            
            wc_polylang_admin_debug_log("Admin erfolgreich initialisiert", 'INFO');
        } catch (Exception $e) {
            wc_polylang_admin_debug_log("Fehler im Admin-Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        wc_polylang_admin_debug_log("Admin-Menü wird registriert", 'DEBUG');
        
        // HAUPTMENÜ
        add_submenu_page(
            'woocommerce',
            __('Polylang Integration', 'wc-polylang-integration'),
            __('🌍 Polylang Integration', 'wc-polylang-integration'),
            'manage_woocommerce',
            'wc-polylang-integration',
            array($this, 'admin_page')
        );
        
        // UNTERMENÜS
        $this->add_submenus();
        
        wc_polylang_admin_debug_log("Admin-Menü erfolgreich registriert", 'INFO');
    }
    
    /**
     * Füge alle Untermenüs hinzu
     */
    private function add_submenus() {
        // Shop-Seiten Konfiguration
        add_submenu_page(
            'wc-polylang-integration',
            __('Shop-Seiten', 'wc-polylang-integration'),
            __('🛍️ Shop-Seiten', 'wc-polylang-integration'),
            'manage_options',
            'wc-polylang-shop-config',
            array($this, 'shop_config_page')
        );
        
        // Bilinguale Kategorien
        add_submenu_page(
            'wc-polylang-integration',
            __('Bilinguale Kategorien', 'wc-polylang-integration'),
            __('🌐 Bilinguale Kategorien', 'wc-polylang-integration'),
            'manage_options',
            'wc-polylang-bilingual-categories',
            array($this, 'bilingual_categories_page')
        );
        
        // Kategorien-Management
        add_submenu_page(
            'wc-polylang-integration',
            __('Kategorien verwalten', 'wc-polylang-integration'),
            __('📁 Kategorien', 'wc-polylang-integration'),
            'manage_options',
            'wc-polylang-categories',
            array($this, 'categories_page')
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
        
        // Debug-Einstellungen
        register_setting('wc_polylang_settings', 'wc_polylang_debug_enabled');
        register_setting('wc_polylang_settings', 'wc_polylang_debug_level');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wc-polylang-integration') === false) {
            return;
        }
        
        wc_polylang_admin_debug_log("Admin-Scripts werden geladen für: " . $hook, 'DEBUG');
    }
    
    /**
     * Admin page - HAUPTSEITE MIT DEBUG-KONFIGURATION
     */
    public function admin_page() {
        wc_polylang_admin_debug_log("Hauptseite wird angezeigt", 'DEBUG');
        
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        // Lade Einstellungen
        $settings = $this->get_settings();
        $stats = $this->get_translation_stats();
        $debug_settings = WC_Polylang_Debug::get_debug_settings();
        
        ?>
        <div class="wrap">
            <h1>🌍 WooCommerce Polylang Integration</h1>
            <p class="description">Entwickelt von <strong><a href="https://www.lipalife.de" target="_blank">LipaLIFE</a></strong> - Professionelle WordPress & WooCommerce Lösungen</p>
            
            <div class="notice notice-success">
                <p><strong>✅ Plugin erfolgreich aktiviert und HPOS-kompatibel!</strong></p>
                <p>Das Plugin ist jetzt vollständig kompatibel mit WooCommerce High-Performance Order Storage (HPOS).</p>
            </div>
            
            <!-- Navigation zu Unterseiten -->
            <div class="wc-polylang-navigation">
                <h2>🚀 Verfügbare Funktionen</h2>
                <div class="nav-cards">
                    <div class="nav-card">
                        <h3>🛍️ Shop-Seiten</h3>
                        <p>Konfigurieren Sie mehrsprachige WooCommerce-Seiten (Shop, Checkout, My Account)</p>
                        <a href="<?php echo admin_url('admin.php?page=wc-polylang-shop-config'); ?>" class="button button-primary">
                            Shop-Seiten konfigurieren
                        </a>
                    </div>
                    <div class="nav-card">
                        <h3>🌐 Bilinguale Kategorien</h3>
                        <p>Zeigen Sie Kategorien in beiden Sprachen gleichzeitig an (Deutsch | English)</p>
                        <a href="<?php echo admin_url('admin.php?page=wc-polylang-bilingual-categories'); ?>" class="button button-primary">
                            Bilinguale Kategorien
                        </a>
                    </div>
                    <div class="nav-card">
                        <h3>📁 Kategorien</h3>
                        <p>Erstellen Sie hierarchische mehrsprachige Kategorie-Strukturen</p>
                        <a href="<?php echo admin_url('admin.php?page=wc-polylang-categories'); ?>" class="button button-primary">
                            Kategorien verwalten
                        </a>
                    </div>
                    <div class="nav-card">
                        <h3>⚙️ Einstellungen</h3>
                        <p>Allgemeine Plugin-Einstellungen und Konfiguration</p>
                        <a href="#settings" class="button button-secondary" onclick="document.getElementById('settings').scrollIntoView();">
                            Zu Einstellungen
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- DEBUG-KONFIGURATION - NEUE SEKTION -->
            <div class="card">
                <h2>🐛 Debug-Konfiguration</h2>
                <p><strong>Debug-Status:</strong> <?php echo $debug_settings['enabled'] ? '✅ Aktiviert' : '❌ Deaktiviert'; ?></p>
                <p><strong>Debug-Level:</strong> <?php echo $this->get_debug_level_name($debug_settings['level']); ?></p>
                <p><strong>Log-Datei:</strong> <code><?php echo $debug_settings['file_path']; ?></code></p>
                <p><strong>Log-Größe:</strong> <?php echo $this->format_file_size($debug_settings['file_size']); ?></p>
                
                <form id="debug-settings-form" style="margin: 20px 0;">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Debug aktivieren</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="debug-enabled" <?php checked($debug_settings['enabled'], true); ?>>
                                    Debug-Logging aktivieren
                                </label>
                                <p class="description">Aktiviert das Logging von Debug-Informationen</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Debug-Level</th>
                            <td>
                                <select id="debug-level">
                                    <option value="0" <?php selected($debug_settings['level'], 0); ?>>Aus (Kein Debug)</option>
                                    <option value="1" <?php selected($debug_settings['level'], 1); ?>>Nur Fehler</option>
                                    <option value="2" <?php selected($debug_settings['level'], 2); ?>>Fehler + Warnungen</option>
                                    <option value="3" <?php selected($debug_settings['level'], 3); ?>>Fehler + Warnungen + Infos</option>
                                    <option value="4" <?php selected($debug_settings['level'], 4); ?>>Alles (Vollständig)</option>
                                </select>
                                <p class="description">Bestimmt welche Arten von Debug-Meldungen geloggt werden</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div style="margin: 20px 0;">
                        <button type="button" id="save-debug-settings" class="button button-primary">💾 Debug-Einstellungen speichern</button>
                        <button type="button" id="clear-debug-log" class="button button-secondary">🗑️ Debug-Log löschen</button>
                        <button type="button" id="show-debug-log" class="button">📋 Debug-Log anzeigen</button>
                        <button type="button" class="button" onclick="location.reload()">🔄 Seite aktualisieren</button>
                    </div>
                </form>
                
                <div id="debug-log-content" style="display:none; background:#f1f1f1; padding:15px; border-radius:4px; max-height:400px; overflow-y:auto; margin-top:20px;">
                    <pre style="white-space: pre-wrap; font-size: 12px;"><?php 
                        if (class_exists('WC_Polylang_Debug')) {
                            echo esc_html(WC_Polylang_Debug::get_log_content()); 
                        } else {
                            echo "Debug-Klasse nicht verfügbar.";
                        }
                    ?></pre>
                </div>
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
            
            <div id="settings">
                <form method="post" action="">
                    <?php wp_nonce_field('wc_polylang_settings', 'wc_polylang_nonce'); ?>
                    
                    <h2>⚙️ Plugin-Einstellungen</h2>
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
                        <td><strong>Debug-Status:</strong></td>
                        <td><?php echo $debug_settings['enabled'] ? '✅ Aktiviert (' . $this->get_debug_level_name($debug_settings['level']) . ')' : '❌ Deaktiviert'; ?></td>
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
                        <td><?php echo defined('WC_POLYLANG_INTEGRATION_VERSION') ? WC_POLYLANG_INTEGRATION_VERSION : '1.2.0'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2 style="color: white;">🌟 LipaLIFE - Ihr WordPress Partner</h2>
                <p><strong>Professionelle WordPress & WooCommerce Entwicklung</strong></p>
                <p>Besuchen Sie uns: <a href="https://www.lipalife.de" target="_blank" style="color: #fff; text-decoration: underline;">www.lipalife.de</a></p>
                <p>Für Support und weitere Plugins kontaktieren Sie uns gerne!</p>
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
        .description {
            font-style: italic;
            margin-bottom: 20px;
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
        
        /* Navigation Cards */
        .wc-polylang-navigation {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .nav-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .nav-card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .nav-card h3 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        .nav-card p {
            margin: 0 0 15px 0;
            color: #666;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Debug-Einstellungen speichern
            $('#save-debug-settings').on('click', function() {
                var enabled = $('#debug-enabled').is(':checked');
                var level = $('#debug-level').val();
                
                var button = $(this);
                button.prop('disabled', true).text('💾 Speichere...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_save_debug_settings',
                        enabled: enabled,
                        level: level,
                        nonce: '<?php echo wp_create_nonce('wc_polylang_debug'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Debug-Einstellungen gespeichert!');
                            location.reload();
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                        button.prop('disabled', false).text('💾 Debug-Einstellungen speichern');
                    }
                });
            });
            
            // Debug-Log anzeigen/verstecken
            $('#show-debug-log').on('click', function() {
                var content = $('#debug-log-content');
                if (content.is(':visible')) {
                    content.hide();
                    $(this).text('📋 Debug-Log anzeigen');
                } else {
                    content.show();
                    $(this).text('📋 Debug-Log verstecken');
                }
            });
            
            // Debug-Log löschen
            $('#clear-debug-log').on('click', function() {
                if (confirm('Möchten Sie wirklich das Debug-Log löschen?')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wc_polylang_clear_debug_log',
                            nonce: '<?php echo wp_create_nonce('wc_polylang_debug'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('✅ Debug-Log gelöscht!');
                                location.reload();
                            } else {
                                alert('❌ Fehler: ' + response.data);
                            }
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Shop Config Page
     */
    public function shop_config_page() {
        if (!class_exists('WC_Polylang_Shop_Config')) {
            require_once WC_POLYLANG_INTEGRATION_PLUGIN_DIR . 'includes/class-wc-polylang-shop-config.php';
        }
        
        if (class_exists('WC_Polylang_Shop_Config')) {
            $shop_config = WC_Polylang_Shop_Config::get_instance();
            $shop_config->admin_page();
        } else {
            echo '<div class="wrap"><h1>Shop-Seiten Konfiguration</h1><p>Klasse nicht gefunden.</p></div>';
        }
    }
    
    /**
     * Bilingual Categories Page
     */
    public function bilingual_categories_page() {
        if (!class_exists('WC_Polylang_Bilingual_Categories')) {
            require_once WC_POLYLANG_INTEGRATION_PLUGIN_DIR . 'includes/class-wc-polylang-bilingual-categories.php';
        }
        
        if (class_exists('WC_Polylang_Bilingual_Categories')) {
            $bilingual_categories = WC_Polylang_Bilingual_Categories::get_instance();
            $bilingual_categories->admin_page();
        } else {
            echo '<div class="wrap"><h1>Bilinguale Kategorien</h1><p>Klasse nicht gefunden.</p></div>';
        }
    }
    
    /**
     * Categories Page
     */
    public function categories_page() {
        if (!class_exists('WC_Polylang_Categories')) {
            require_once WC_POLYLANG_INTEGRATION_PLUGIN_DIR . 'includes/class-wc-polylang-categories.php';
        }
        
        if (class_exists('WC_Polylang_Categories')) {
            $categories = WC_Polylang_Categories::get_instance();
            $categories->admin_page();
        } else {
            echo '<div class="wrap"><h1>Kategorien verwalten</h1><p>Klasse nicht gefunden.</p></div>';
        }
    }
    
    /**
     * Get settings with fallback
     */
    private function get_settings() {
        return array(
            'enable_product_translation' => get_option('wc_polylang_enable_product_translation', 'yes'),
            'enable_widget_translation' => get_option('wc_polylang_enable_widget_translation', 'yes'),
            'enable_email_translation' => get_option('wc_polylang_enable_email_translation', 'yes'),
            'enable_seo_translation' => get_option('wc_polylang_enable_seo_translation', 'yes'),
        );
    }
    
    /**
     * Get translation stats with fallback
     */
    private function get_translation_stats() {
        return array(
            'products' => array(
                'total' => wp_count_posts('product')->publish ?? 0,
                'translated' => 0,
                'percentage' => 0
            ),
            'categories' => array(
                'total' => wp_count_terms('product_cat') ?? 0,
                'translated' => 0,
                'percentage' => 0
            )
        );
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        wc_polylang_admin_debug_log("Einstellungen werden gespeichert", 'INFO');
        
        if (!wp_verify_nonce($_POST['wc_polylang_nonce'], 'wc_polylang_settings')) {
            wc_polylang_admin_debug_log("Nonce-Verifikation fehlgeschlagen", 'ERROR');
            return;
        }
        
        $settings = array(
            'enable_product_translation' => isset($_POST['enable_product_translation']) ? 'yes' : 'no',
            'enable_widget_translation' => isset($_POST['enable_widget_translation']) ? 'yes' : 'no',
            'enable_email_translation' => isset($_POST['enable_email_translation']) ? 'yes' : 'no',
            'enable_seo_translation' => isset($_POST['enable_seo_translation']) ? 'yes' : 'no',
        );
        
        foreach ($settings as $key => $value) {
            update_option('wc_polylang_' . $key, $value);
        }
        
        echo '<div class="notice notice-success"><p>' . __('Einstellungen gespeichert.', 'wc-polylang-integration') . '</p></div>';
        wc_polylang_admin_debug_log("Einstellungen erfolgreich gespeichert", 'SUCCESS');
    }
    
    /**
     * Hole Debug-Level Namen
     */
    private function get_debug_level_name($level) {
        switch ($level) {
            case 0: return 'Aus';
            case 1: return 'Nur Fehler';
            case 2: return 'Fehler + Warnungen';
            case 3: return 'Fehler + Warnungen + Infos';
            case 4: return 'Vollständig';
            default: return 'Unbekannt';
        }
    }
    
    /**
     * Formatiere Dateigröße
     */
    private function format_file_size($bytes) {
        if ($bytes == 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = array('Bytes', 'KB', 'MB', 'GB');
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}
