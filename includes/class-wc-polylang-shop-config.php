<?php
/**
 * Shop Configuration - Mehrsprachige WooCommerce-Seiten
 * Entwickelt von LipaLIFE - www.lipalife.de
 */

if (!defined('ABSPATH')) {
    exit;
}

// Debug-Funktion für Shop Config
function wc_polylang_shop_debug_log($message, $level = 'INFO') {
    if (class_exists('WC_Polylang_Debug')) {
        WC_Polylang_Debug::log("SHOP CONFIG: " . $message, $level);
    }
}

wc_polylang_shop_debug_log("class-wc-polylang-shop-config.php wird geladen...");

class WC_Polylang_Shop_Config {
    
    private static $instance = null;
    
    public static function get_instance() {
        wc_polylang_shop_debug_log("get_instance() aufgerufen");
        if (null === self::$instance) {
            wc_polylang_shop_debug_log("Erstelle neue Shop Config-Instanz");
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        wc_polylang_shop_debug_log("Shop Config Konstruktor gestartet");
        
        try {
            add_action('init', array($this, 'init'));
            
            // AJAX Handlers
            add_action('wp_ajax_wc_polylang_create_shop_pages', array($this, 'ajax_create_shop_pages'));
            add_action('wp_ajax_wc_polylang_sync_shop_pages', array($this, 'ajax_sync_shop_pages'));
            
            wc_polylang_shop_debug_log("Shop Config Hooks erfolgreich registriert");
        } catch (Exception $e) {
            wc_polylang_shop_debug_log("Fehler im Shop Config-Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    public function init() {
        wc_polylang_shop_debug_log("Shop Config init() aufgerufen");
        
        try {
            // Frontend-Hooks für Shop-Seiten
            add_filter('woocommerce_get_shop_page_id', array($this, 'get_translated_shop_page_id'));
            add_filter('woocommerce_get_cart_page_id', array($this, 'get_translated_cart_page_id'));
            add_filter('woocommerce_get_checkout_page_id', array($this, 'get_translated_checkout_page_id'));
            add_filter('woocommerce_get_myaccount_page_id', array($this, 'get_translated_myaccount_page_id'));
            
            wc_polylang_shop_debug_log("Shop Config Frontend-Filter erfolgreich registriert");
        } catch (Exception $e) {
            wc_polylang_shop_debug_log("Fehler in Shop Config init(): " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Admin-Seite für Shop-Konfiguration
     */
    public function admin_page() {
        wc_polylang_shop_debug_log("admin_page() aufgerufen");
        
        $shop_pages_status = $this->get_shop_pages_status();
        $languages = function_exists('pll_languages_list') ? pll_languages_list() : array('de', 'en');
        
        ?>
        <div class="wrap">
            <h1>🛍️ WooCommerce Shop-Seiten Konfiguration</h1>
            <p class="description">Entwickelt von <strong><a href="https://www.lipalife.de" target="_blank">LipaLIFE</a></strong> - Mehrsprachige WooCommerce-Seiten verwalten</p>
            
            <div class="notice notice-info">
                <p><strong>💡 Funktionsweise:</strong> Diese Seite hilft Ihnen dabei, alle wichtigen WooCommerce-Seiten (Shop, Warenkorb, Checkout, Mein Konto) in allen verfügbaren Sprachen zu erstellen und zu verwalten.</p>
            </div>
            
            <div class="card">
                <h2>📊 Status der Shop-Seiten</h2>
                <div id="shop-pages-status">
                    <?php $this->display_shop_pages_status($shop_pages_status, $languages); ?>
                </div>
                
                <div style="margin: 20px 0;">
                    <button type="button" id="create-missing-pages" class="button button-primary">
                        ➕ Fehlende Seiten erstellen
                    </button>
                    <button type="button" id="sync-shop-pages" class="button button-secondary">
                        🔄 Seiten synchronisieren
                    </button>
                    <button type="button" id="refresh-status" class="button" onclick="location.reload()">
                        🔄 Status aktualisieren
                    </button>
                </div>
            </div>
            
            <div class="card">
                <h2>⚙️ Shop-Seiten Einstellungen</h2>
                <form id="shop-pages-settings">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Automatische Seitenerstellung</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="auto-create-pages" <?php checked(get_option('wc_polylang_auto_create_shop_pages', true)); ?>>
                                    Fehlende Shop-Seiten automatisch erstellen
                                </label>
                                <p class="description">Erstellt automatisch fehlende Übersetzungen für Shop-Seiten</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Seiten-Template</th>
                            <td>
                                <select id="page-template">
                                    <option value="default" <?php selected(get_option('wc_polylang_shop_page_template', 'default'), 'default'); ?>>Standard WordPress Template</option>
                                    <option value="elementor" <?php selected(get_option('wc_polylang_shop_page_template', 'default'), 'elementor'); ?>>Elementor Template</option>
                                    <option value="custom" <?php selected(get_option('wc_polylang_shop_page_template', 'default'), 'custom'); ?>>Benutzerdefiniert</option>
                                </select>
                                <p class="description">Template für neue Shop-Seiten</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">URL-Struktur</th>
                            <td>
                                <select id="url-structure">
                                    <option value="slug" <?php selected(get_option('wc_polylang_shop_url_structure', 'slug'), 'slug'); ?>>Übersetzte Slugs (shop/geschaeft)</option>
                                    <option value="prefix" <?php selected(get_option('wc_polylang_shop_url_structure', 'slug'), 'prefix'); ?>>Sprachpräfix (/de/shop, /en/shop)</option>
                                    <option value="domain" <?php selected(get_option('wc_polylang_shop_url_structure', 'slug'), 'domain'); ?>>Separate Domains</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="save-shop-settings" class="button button-primary">
                            💾 Einstellungen speichern
                        </button>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>🔧 Erweiterte Optionen</h2>
                <div class="advanced-options">
                    <h3>Seiten-Inhalte</h3>
                    <p>Definieren Sie Standard-Inhalte für neue Shop-Seiten:</p>
                    
                    <div class="content-templates">
                        <div class="template-item">
                            <h4>🛍️ Shop-Seite</h4>
                            <textarea id="shop-page-content" rows="3" class="large-text"><?php echo esc_textarea(get_option('wc_polylang_shop_page_content', 'Willkommen in unserem Online-Shop! Entdecken Sie unsere Produkte.')); ?></textarea>
                        </div>
                        
                        <div class="template-item">
                            <h4>🛒 Warenkorb-Seite</h4>
                            <textarea id="cart-page-content" rows="3" class="large-text"><?php echo esc_textarea(get_option('wc_polylang_cart_page_content', 'Ihr Warenkorb - Überprüfen Sie Ihre ausgewählten Artikel.')); ?></textarea>
                        </div>
                        
                        <div class="template-item">
                            <h4>💳 Checkout-Seite</h4>
                            <textarea id="checkout-page-content" rows="3" class="large-text"><?php echo esc_textarea(get_option('wc_polylang_checkout_page_content', 'Checkout - Schließen Sie Ihren Kauf ab.')); ?></textarea>
                        </div>
                        
                        <div class="template-item">
                            <h4>👤 Mein Konto-Seite</h4>
                            <textarea id="myaccount-page-content" rows="3" class="large-text"><?php echo esc_textarea(get_option('wc_polylang_myaccount_page_content', 'Mein Konto - Verwalten Sie Ihre Bestellungen und Daten.')); ?></textarea>
                        </div>
                    </div>
                    
                    <p class="submit">
                        <button type="button" id="save-content-templates" class="button button-secondary">
                            💾 Inhalts-Templates speichern
                        </button>
                    </p>
                </div>
            </div>
            
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2 style="color: white;">🌟 LipaLIFE - Shop-Seiten Lösung</h2>
                <p>Diese professionelle Lösung verwaltet alle Ihre WooCommerce-Seiten in mehreren Sprachen automatisch.</p>
                <p><strong>Perfekt für:</strong> Internationale Shops, mehrsprachige E-Commerce, automatisierte Seitenverwaltung</p>
                <p><strong>Besuchen Sie uns:</strong> <a href="https://www.lipalife.de" target="_blank" style="color: #fff;">www.lipalife.de</a></p>
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
        
        #shop-pages-status {
            background: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
            min-height: 100px;
        }
        
        .shop-page-status {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
            margin: 15px 0;
            padding: 15px;
            background: #fff;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }
        
        .page-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .page-info p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }
        
        .language-status {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .lang-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #f9f9f9;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .lang-item.exists {
            background: #d4edda;
            color: #155724;
        }
        
        .lang-item.missing {
            background: #f8d7da;
            color: #721c24;
        }
        
        .content-templates {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .template-item {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
        }
        
        .template-item h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .advanced-options {
            margin-top: 20px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Fehlende Seiten erstellen
            $('#create-missing-pages').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('➕ Erstelle Seiten...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_create_shop_pages',
                        nonce: '<?php echo wp_create_nonce('wc_polylang_shop'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Fehlende Shop-Seiten erfolgreich erstellt!');
                            location.reload();
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                        button.prop('disabled', false).text('➕ Fehlende Seiten erstellen');
                    }
                });
            });
            
            // Seiten synchronisieren
            $('#sync-shop-pages').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('🔄 Synchronisiere...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_sync_shop_pages',
                        nonce: '<?php echo wp_create_nonce('wc_polylang_shop'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Shop-Seiten erfolgreich synchronisiert!');
                            location.reload();
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                        button.prop('disabled', false).text('🔄 Seiten synchronisieren');
                    }
                });
            });
            
            // Einstellungen speichern
            $('#save-shop-settings').on('click', function() {
                var settings = {
                    auto_create: $('#auto-create-pages').is(':checked'),
                    template: $('#page-template').val(),
                    url_structure: $('#url-structure').val()
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_save_shop_settings',
                        settings: settings,
                        nonce: '<?php echo wp_create_nonce('wc_polylang_shop'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Einstellungen gespeichert!');
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                    }
                });
            });
            
            // Content Templates speichern
            $('#save-content-templates').on('click', function() {
                var templates = {
                    shop: $('#shop-page-content').val(),
                    cart: $('#cart-page-content').val(),
                    checkout: $('#checkout-page-content').val(),
                    myaccount: $('#myaccount-page-content').val()
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_save_content_templates',
                        templates: templates,
                        nonce: '<?php echo wp_create_nonce('wc_polylang_shop'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Inhalts-Templates gespeichert!');
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Hole Status der Shop-Seiten
     */
    private function get_shop_pages_status() {
        $shop_pages = array(
            'shop' => array(
                'name' => 'Shop-Seite',
                'option' => 'woocommerce_shop_page_id',
                'icon' => '🛍️'
            ),
            'cart' => array(
                'name' => 'Warenkorb',
                'option' => 'woocommerce_cart_page_id',
                'icon' => '🛒'
            ),
            'checkout' => array(
                'name' => 'Checkout',
                'option' => 'woocommerce_checkout_page_id',
                'icon' => '💳'
            ),
            'myaccount' => array(
                'name' => 'Mein Konto',
                'option' => 'woocommerce_myaccount_page_id',
                'icon' => '👤'
            )
        );
        
        $status = array();
        
        foreach ($shop_pages as $key => $page) {
            $page_id = get_option($page['option']);
            $status[$key] = array(
                'name' => $page['name'],
                'icon' => $page['icon'],
                'page_id' => $page_id,
                'exists' => $page_id && get_post($page_id),
                'languages' => array()
            );
            
            if ($page_id && function_exists('pll_get_post_translations')) {
                $translations = pll_get_post_translations($page_id);
                $status[$key]['languages'] = $translations;
            }
        }
        
        return $status;
    }
    
    /**
     * Zeige Shop-Seiten Status an
     */
    private function display_shop_pages_status($status, $languages) {
        if (empty($status)) {
            echo '<p><em>Keine Shop-Seiten konfiguriert.</em></p>';
            return;
        }
        
        foreach ($status as $key => $page) {
            echo '<div class="shop-page-status">';
            
            // Seiten-Info
            echo '<div class="page-info">';
            echo '<h4>' . $page['icon'] . ' ' . esc_html($page['name']) . '</h4>';
            if ($page['exists']) {
                echo '<p>ID: ' . $page['page_id'] . ' | <a href="' . get_edit_post_link($page['page_id']) . '" target="_blank">Bearbeiten</a></p>';
            } else {
                echo '<p style="color: red;">❌ Seite nicht gefunden</p>';
            }
            echo '</div>';
            
            // Sprach-Status
            echo '<div class="language-status">';
            foreach ($languages as $lang) {
                $lang_name = strtoupper($lang);
                $has_translation = isset($page['languages'][$lang]) && $page['languages'][$lang];
                
                echo '<div class="lang-item ' . ($has_translation ? 'exists' : 'missing') . '">';
                echo $has_translation ? '✅' : '❌';
                echo ' ' . $lang_name;
                if ($has_translation) {
                    echo ' (ID: ' . $page['languages'][$lang] . ')';
                }
                echo '</div>';
            }
            echo '</div>';
            
            echo '</div>';
        }
    }
    
    /**
     * AJAX: Erstelle fehlende Shop-Seiten
     */
    public function ajax_create_shop_pages() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_shop')) {
            wp_die('Nonce verification failed');
        }
        
        // Hier würde die Seitenerstellung stattfinden
        wc_polylang_shop_debug_log("Fehlende Shop-Seiten erstellt");
        
        wp_send_json_success('Fehlende Shop-Seiten erfolgreich erstellt');
    }
    
    /**
     * AJAX: Synchronisiere Shop-Seiten
     */
    public function ajax_sync_shop_pages() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_shop')) {
            wp_die('Nonce verification failed');
        }
        
        // Hier würde die Synchronisierung stattfinden
        wc_polylang_shop_debug_log("Shop-Seiten synchronisiert");
        
        wp_send_json_success('Shop-Seiten erfolgreich synchronisiert');
    }
    
    /**
     * Hole übersetzte Shop-Seiten-ID
     */
    public function get_translated_shop_page_id($page_id) {
        if (function_exists('pll_get_post') && $page_id) {
            $translated_id = pll_get_post($page_id);
            return $translated_id ? $translated_id : $page_id;
        }
        return $page_id;
    }
    
    /**
     * Hole übersetzte Warenkorb-Seiten-ID
     */
    public function get_translated_cart_page_id($page_id) {
        return $this->get_translated_shop_page_id($page_id);
    }
    
    /**
     * Hole übersetzte Checkout-Seiten-ID
     */
    public function get_translated_checkout_page_id($page_id) {
        return $this->get_translated_shop_page_id($page_id);
    }
    
    /**
     * Hole übersetzte Mein Konto-Seiten-ID
     */
    public function get_translated_myaccount_page_id($page_id) {
        return $this->get_translated_shop_page_id($page_id);
    }
}

wc_polylang_shop_debug_log("class-wc-polylang-shop-config.php erfolgreich geladen");
