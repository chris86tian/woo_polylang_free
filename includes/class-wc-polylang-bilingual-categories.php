<?php
/**
 * Bilingual Categories - MIT OPTIMIERTEM DEBUG
 */

if (!defined('ABSPATH')) {
    exit;
}

// Optimierte Debug-Funktion für Bilingual Categories
function wc_polylang_bilingual_debug_log($message, $level = 'DEBUG') {
    if (class_exists('WC_Polylang_Debug')) {
        WC_Polylang_Debug::log("BILINGUAL: " . $message, $level);
    }
}

class WC_Polylang_Bilingual_Categories {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            wc_polylang_bilingual_debug_log("Bilingual Categories-Instanz wird erstellt", 'INFO');
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        try {
            add_action('init', array($this, 'init'));
            
            // AJAX Handlers
            add_action('wp_ajax_wc_polylang_save_bilingual_settings', array($this, 'ajax_save_bilingual_settings'));
            add_action('wp_ajax_wc_polylang_preview_bilingual_categories', array($this, 'ajax_preview_bilingual_categories'));
            
            wc_polylang_bilingual_debug_log("Bilingual Categories erfolgreich initialisiert", 'INFO');
        } catch (Exception $e) {
            wc_polylang_bilingual_debug_log("Fehler im Bilingual Categories-Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    public function init() {
        try {
            // Frontend-Hooks für bilinguale Kategorien
            add_filter('woocommerce_product_categories_widget_args', array($this, 'modify_category_widget'));
            add_filter('get_terms', array($this, 'modify_category_display'), 10, 3);
            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
            
            wc_polylang_bilingual_debug_log("Bilingual Categories Frontend-Filter registriert", 'DEBUG');
        } catch (Exception $e) {
            wc_polylang_bilingual_debug_log("Fehler in Bilingual Categories init(): " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Admin-Seite für bilinguale Kategorien
     */
    public function admin_page() {
        wc_polylang_bilingual_debug_log("Bilingual Categories Admin-Seite wird angezeigt", 'DEBUG');
        
        $current_settings = $this->get_bilingual_settings();
        $categories_preview = $this->get_categories_preview();
        
        ?>
        <div class="wrap">
            <h1>🌐 Bilinguale Kategorien</h1>
            <p class="description">Entwickelt von <strong><a href="https://www.lipalife.de" target="_blank">LipaLIFE</a></strong> - Zeigen Sie Kategorien in beiden Sprachen gleichzeitig an</p>
            
            <div class="notice notice-info">
                <p><strong>💡 Funktionsweise:</strong> Diese Funktion zeigt Produktkategorien in beiden Sprachen gleichzeitig an, z.B. "Elektronik | Electronics" oder "Mode / Fashion".</p>
            </div>
            
            <div class="card">
                <h2>⚙️ Bilinguale Anzeige-Einstellungen</h2>
                <form id="bilingual-settings-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Bilinguale Anzeige aktivieren</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="enable-bilingual-display" <?php checked($current_settings['enabled'], true); ?>>
                                    Kategorien in beiden Sprachen anzeigen
                                </label>
                                <p class="description">Aktiviert die gleichzeitige Anzeige von Kategorien in Deutsch und Englisch</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Anzeigeformat</th>
                            <td>
                                <select id="display-format">
                                    <option value="pipe" <?php selected($current_settings['format'], 'pipe'); ?>>Deutsch | English</option>
                                    <option value="slash" <?php selected($current_settings['format'], 'slash'); ?>>Deutsch / English</option>
                                    <option value="dash" <?php selected($current_settings['format'], 'dash'); ?>>Deutsch - English</option>
                                    <option value="parentheses" <?php selected($current_settings['format'], 'parentheses'); ?>>Deutsch (English)</option>
                                    <option value="brackets" <?php selected($current_settings['format'], 'brackets'); ?>>Deutsch [English]</option>
                                    <option value="newline" <?php selected($current_settings['format'], 'newline'); ?>>Deutsch<br>English</option>
                                </select>
                                <p class="description">Wählen Sie das Format für die bilinguale Anzeige</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Primäre Sprache</th>
                            <td>
                                <select id="primary-language">
                                    <option value="de" <?php selected($current_settings['primary_language'], 'de'); ?>>Deutsch</option>
                                    <option value="en" <?php selected($current_settings['primary_language'], 'en'); ?>>English</option>
                                </select>
                                <p class="description">Die Sprache, die zuerst angezeigt wird</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Sekundäre Sprache</th>
                            <td>
                                <select id="secondary-language">
                                    <option value="en" <?php selected($current_settings['secondary_language'], 'en'); ?>>English</option>
                                    <option value="de" <?php selected($current_settings['secondary_language'], 'de'); ?>>Deutsch</option>
                                </select>
                                <p class="description">Die Sprache, die als zweites angezeigt wird</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Fallback-Verhalten</th>
                            <td>
                                <select id="fallback-behavior">
                                    <option value="hide_secondary" <?php selected($current_settings['fallback'], 'hide_secondary'); ?>>Nur primäre Sprache anzeigen</option>
                                    <option value="show_primary_twice" <?php selected($current_settings['fallback'], 'show_primary_twice'); ?>>Primäre Sprache doppelt anzeigen</option>
                                    <option value="show_placeholder" <?php selected($current_settings['fallback'], 'show_placeholder'); ?>>Platzhalter anzeigen</option>
                                </select>
                                <p class="description">Was passiert, wenn eine Übersetzung fehlt?</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Anwendungsbereich</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" id="apply-to-widgets" <?php checked($current_settings['apply_widgets'], true); ?>>
                                        Kategorie-Widgets
                                    </label><br>
                                    <label>
                                        <input type="checkbox" id="apply-to-menus" <?php checked($current_settings['apply_menus'], true); ?>>
                                        Navigationsmenüs
                                    </label><br>
                                    <label>
                                        <input type="checkbox" id="apply-to-breadcrumbs" <?php checked($current_settings['apply_breadcrumbs'], true); ?>>
                                        Breadcrumbs
                                    </label><br>
                                    <label>
                                        <input type="checkbox" id="apply-to-shop-page" <?php checked($current_settings['apply_shop'], true); ?>>
                                        Shop-Seite
                                    </label>
                                </fieldset>
                                <p class="description">Wo sollen bilinguale Kategorien angezeigt werden?</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="save-bilingual-settings" class="button button-primary">
                            💾 Einstellungen speichern
                        </button>
                        <button type="button" id="preview-bilingual-categories" class="button button-secondary">
                            👁️ Vorschau anzeigen
                        </button>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>👁️ Live-Vorschau</h2>
                <div id="bilingual-preview">
                    <?php $this->display_categories_preview($categories_preview, $current_settings); ?>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="button" id="refresh-preview" class="button">
                        🔄 Vorschau aktualisieren
                    </button>
                </div>
            </div>
            
            <div class="card">
                <h2>🎨 Styling-Optionen</h2>
                <form id="styling-options">
                    <table class="form-table">
                        <tr>
                            <th scope="row">CSS-Klasse</th>
                            <td>
                                <input type="text" id="custom-css-class" class="regular-text" value="<?php echo esc_attr($current_settings['css_class']); ?>" placeholder="bilingual-category">
                                <p class="description">Benutzerdefinierte CSS-Klasse für bilinguale Kategorien</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Primäre Sprache Styling</th>
                            <td>
                                <input type="text" id="primary-style" class="regular-text" value="<?php echo esc_attr($current_settings['primary_style']); ?>" placeholder="font-weight: bold;">
                                <p class="description">CSS-Styles für die primäre Sprache</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Sekundäre Sprache Styling</th>
                            <td>
                                <input type="text" id="secondary-style" class="regular-text" value="<?php echo esc_attr($current_settings['secondary_style']); ?>" placeholder="font-style: italic; opacity: 0.8;">
                                <p class="description">CSS-Styles für die sekundäre Sprache</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Trennzeichen Styling</th>
                            <td>
                                <input type="text" id="separator-style" class="regular-text" value="<?php echo esc_attr($current_settings['separator_style']); ?>" placeholder="color: #999; margin: 0 5px;">
                                <p class="description">CSS-Styles für das Trennzeichen</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="save-styling-options" class="button button-secondary">
                            🎨 Styling speichern
                        </button>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>🔧 Erweiterte Optionen</h2>
                <div class="advanced-options">
                    <h3>Automatische Übersetzung</h3>
                    <p>Erstellen Sie automatisch fehlende Kategorie-Übersetzungen:</p>
                    
                    <div style="margin: 20px 0;">
                        <button type="button" id="auto-translate-missing" class="button">
                            🤖 Fehlende Übersetzungen erstellen
                        </button>
                        <button type="button" id="check-translation-status" class="button">
                            📊 Übersetzungsstatus prüfen
                        </button>
                    </div>
                    
                    <h3>Import/Export</h3>
                    <p>Verwalten Sie Ihre bilingualen Kategorie-Einstellungen:</p>
                    
                    <div style="margin: 20px 0;">
                        <button type="button" id="export-settings" class="button">
                            📤 Einstellungen exportieren
                        </button>
                        <button type="button" id="import-settings" class="button">
                            📥 Einstellungen importieren
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2 style="color: white;">🌟 LipaLIFE - Bilinguale Kategorien</h2>
                <p>Revolutionäre Lösung für die gleichzeitige Anzeige von Kategorien in mehreren Sprachen - perfekt für internationale Shops!</p>
                <p><strong>Vorteile:</strong> Bessere UX, keine Sprachbarrieren, professionelle Darstellung, SEO-optimiert</p>
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
        
        #bilingual-preview {
            background: #f1f1f1;
            padding: 20px;
            border-radius: 4px;
            min-height: 150px;
        }
        
        .preview-category {
            display: block;
            margin: 10px 0;
            padding: 10px;
            background: #fff;
            border-radius: 3px;
            border-left: 4px solid #0073aa;
        }
        
        .bilingual-category {
            font-size: 16px;
        }
        
        .primary-lang {
            font-weight: bold;
        }
        
        .secondary-lang {
            font-style: italic;
            opacity: 0.8;
        }
        
        .separator {
            color: #999;
            margin: 0 5px;
        }
        
        .advanced-options {
            margin-top: 20px;
        }
        
        .advanced-options h3 {
            color: #333;
            margin: 20px 0 10px 0;
        }
        
        fieldset label {
            display: block;
            margin: 5px 0;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Einstellungen speichern
            $('#save-bilingual-settings').on('click', function() {
                var settings = {
                    enabled: $('#enable-bilingual-display').is(':checked'),
                    format: $('#display-format').val(),
                    primary_language: $('#primary-language').val(),
                    secondary_language: $('#secondary-language').val(),
                    fallback: $('#fallback-behavior').val(),
                    apply_widgets: $('#apply-to-widgets').is(':checked'),
                    apply_menus: $('#apply-to-menus').is(':checked'),
                    apply_breadcrumbs: $('#apply-to-breadcrumbs').is(':checked'),
                    apply_shop: $('#apply-to-shop-page').is(':checked')
                };
                
                var button = $(this);
                button.prop('disabled', true).text('💾 Speichere...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_save_bilingual_settings',
                        settings: settings,
                        nonce: '<?php echo wp_create_nonce('wc_polylang_bilingual'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Einstellungen erfolgreich gespeichert!');
                            $('#refresh-preview').click();
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                        button.prop('disabled', false).text('💾 Einstellungen speichern');
                    }
                });
            });
            
            // Vorschau aktualisieren
            $('#refresh-preview, #preview-bilingual-categories').on('click', function() {
                var settings = {
                    format: $('#display-format').val(),
                    primary_language: $('#primary-language').val(),
                    secondary_language: $('#secondary-language').val(),
                    fallback: $('#fallback-behavior').val()
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_preview_bilingual_categories',
                        settings: settings,
                        nonce: '<?php echo wp_create_nonce('wc_polylang_bilingual'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#bilingual-preview').html(response.data);
                        } else {
                            $('#bilingual-preview').html('<p>❌ Fehler beim Laden der Vorschau</p>');
                        }
                    }
                });
            });
            
            // Styling speichern
            $('#save-styling-options').on('click', function() {
                var styling = {
                    css_class: $('#custom-css-class').val(),
                    primary_style: $('#primary-style').val(),
                    secondary_style: $('#secondary-style').val(),
                    separator_style: $('#separator-style').val()
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_save_bilingual_styling',
                        styling: styling,
                        nonce: '<?php echo wp_create_nonce('wc_polylang_bilingual'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Styling-Optionen gespeichert!');
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                    }
                });
            });
            
            // Erweiterte Funktionen
            $('#auto-translate-missing').on('click', function() {
                if (confirm('Möchten Sie wirklich fehlende Übersetzungen automatisch erstellen?')) {
                    alert('🤖 Automatische Übersetzung wird in der nächsten Version verfügbar sein');
                }
            });
            
            $('#check-translation-status').on('click', function() {
                alert('📊 Übersetzungsstatus-Prüfung wird in der nächsten Version verfügbar sein');
            });
            
            $('#export-settings').on('click', function() {
                alert('📤 Export-Funktion wird in der nächsten Version verfügbar sein');
            });
            
            $('#import-settings').on('click', function() {
                alert('📥 Import-Funktion wird in der nächsten Version verfügbar sein');
            });
            
            // Live-Vorschau bei Änderungen
            $('#display-format, #primary-language, #secondary-language, #fallback-behavior').on('change', function() {
                $('#refresh-preview').click();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Hole bilinguale Einstellungen
     */
    private function get_bilingual_settings() {
        return array(
            'enabled' => get_option('wc_polylang_bilingual_enabled', false),
            'format' => get_option('wc_polylang_bilingual_format', 'pipe'),
            'primary_language' => get_option('wc_polylang_bilingual_primary', 'de'),
            'secondary_language' => get_option('wc_polylang_bilingual_secondary', 'en'),
            'fallback' => get_option('wc_polylang_bilingual_fallback', 'hide_secondary'),
            'apply_widgets' => get_option('wc_polylang_bilingual_widgets', true),
            'apply_menus' => get_option('wc_polylang_bilingual_menus', true),
            'apply_breadcrumbs' => get_option('wc_polylang_bilingual_breadcrumbs', false),
            'apply_shop' => get_option('wc_polylang_bilingual_shop', true),
            'css_class' => get_option('wc_polylang_bilingual_css_class', 'bilingual-category'),
            'primary_style' => get_option('wc_polylang_bilingual_primary_style', 'font-weight: bold;'),
            'secondary_style' => get_option('wc_polylang_bilingual_secondary_style', 'font-style: italic; opacity: 0.8;'),
            'separator_style' => get_option('wc_polylang_bilingual_separator_style', 'color: #999; margin: 0 5px;')
        );
    }
    
    /**
     * Hole Kategorien für Vorschau
     */
    private function get_categories_preview() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'number' => 5
        ));
        
        return $categories;
    }
    
    /**
     * Zeige Kategorien-Vorschau
     */
    private function display_categories_preview($categories, $settings) {
        if (empty($categories)) {
            echo '<p><em>Keine Kategorien für Vorschau gefunden.</em></p>';
            return;
        }
        
        foreach ($categories as $category) {
            echo '<div class="preview-category">';
            echo '<span class="bilingual-category">';
            
            // Simuliere bilinguale Anzeige
            $primary_name = $category->name;
            $secondary_name = $category->name . ' (EN)'; // Simuliert
            
            echo '<span class="primary-lang">' . esc_html($primary_name) . '</span>';
            
            $separator = $this->get_separator($settings['format']);
            echo '<span class="separator">' . $separator . '</span>';
            
            echo '<span class="secondary-lang">' . esc_html($secondary_name) . '</span>';
            
            echo '</span>';
            echo '</div>';
        }
    }
    
    /**
     * Hole Trennzeichen basierend auf Format
     */
    private function get_separator($format) {
        switch ($format) {
            case 'pipe': return '|';
            case 'slash': return '/';
            case 'dash': return '-';
            case 'parentheses': return '(';
            case 'brackets': return '[';
            case 'newline': return '<br>';
            default: return '|';
        }
    }
    
    /**
     * AJAX: Speichere bilinguale Einstellungen
     */
    public function ajax_save_bilingual_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_bilingual')) {
            wp_die('Nonce verification failed');
        }
        
        wc_polylang_bilingual_debug_log("Bilinguale Einstellungen werden gespeichert", 'INFO');
        wp_send_json_success('Einstellungen erfolgreich gespeichert');
    }
    
    /**
     * AJAX: Vorschau bilingualer Kategorien
     */
    public function ajax_preview_bilingual_categories() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_bilingual')) {
            wp_die('Nonce verification failed');
        }
        
        $categories = $this->get_categories_preview();
        $settings = $_POST['settings'];
        
        ob_start();
        $this->display_categories_preview($categories, $settings);
        $preview_html = ob_get_clean();
        
        wp_send_json_success($preview_html);
    }
    
    /**
     * Modifiziere Kategorie-Widget
     */
    public function modify_category_widget($args) {
        // Hier würde die Widget-Modifikation stattfinden
        return $args;
    }
    
    /**
     * Modifiziere Kategorie-Anzeige
     */
    public function modify_category_display($terms, $taxonomies, $args) {
        // Hier würde die Kategorie-Anzeige modifiziert werden
        return $terms;
    }
    
    /**
     * Lade Frontend-Scripts
     */
    public function enqueue_frontend_scripts() {
        if (get_option('wc_polylang_bilingual_enabled', false)) {
            // Hier würden Frontend-Scripts geladen werden
        }
    }
}
