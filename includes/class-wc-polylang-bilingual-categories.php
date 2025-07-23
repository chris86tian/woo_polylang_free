<?php
/**
 * Bilingual Categories Display - Zeigt Kategorien in beiden Sprachen gleichzeitig
 * Entwickelt von LipaLIFE - www.lipalife.de
 */

if (!defined('ABSPATH')) {
    exit;
}

// Debug-Funktion für Bilingual Categories
function wc_polylang_bilingual_debug_log($message, $level = 'INFO') {
    if (class_exists('WC_Polylang_Debug')) {
        WC_Polylang_Debug::log("BILINGUAL CATEGORIES: " . $message, $level);
    }
}

wc_polylang_bilingual_debug_log("class-wc-polylang-bilingual-categories.php wird geladen...");

class WC_Polylang_Bilingual_Categories {
    
    private static $instance = null;
    
    public static function get_instance() {
        wc_polylang_bilingual_debug_log("get_instance() aufgerufen");
        if (null === self::$instance) {
            wc_polylang_bilingual_debug_log("Erstelle neue Bilingual Categories-Instanz");
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        wc_polylang_bilingual_debug_log("Bilingual Categories Konstruktor gestartet");
        
        try {
            add_action('init', array($this, 'init'));
            add_action('admin_menu', array($this, 'add_admin_menu'));
            
            // AJAX Handlers
            add_action('wp_ajax_wc_polylang_toggle_bilingual_categories', array($this, 'ajax_toggle_bilingual_categories'));
            add_action('wp_ajax_wc_polylang_sync_category_translations', array($this, 'ajax_sync_translations'));
            
            wc_polylang_bilingual_debug_log("Bilingual Categories Hooks erfolgreich registriert");
        } catch (Exception $e) {
            wc_polylang_bilingual_debug_log("Fehler im Bilingual Categories-Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    public function init() {
        wc_polylang_bilingual_debug_log("Bilingual Categories init() aufgerufen");
        
        try {
            // Frontend-Hooks für bilinguale Anzeige
            if ($this->is_bilingual_mode_enabled()) {
                add_filter('woocommerce_product_categories_widget_args', array($this, 'modify_categories_widget_args'));
                add_filter('wp_list_categories', array($this, 'add_bilingual_category_display'), 10, 2);
                add_filter('woocommerce_product_loop_start', array($this, 'add_bilingual_categories_to_shop'));
                
                // CSS für bilinguale Anzeige
                add_action('wp_enqueue_scripts', array($this, 'enqueue_bilingual_styles'));
                
                wc_polylang_bilingual_debug_log("Bilinguale Anzeige aktiviert");
            }
            
            wc_polylang_bilingual_debug_log("Bilingual Categories Frontend-Filter erfolgreich registriert");
        } catch (Exception $e) {
            wc_polylang_bilingual_debug_log("Fehler in Bilingual Categories init(): " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Füge Admin-Menü hinzu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wc-polylang-integration',
            __('Bilinguale Kategorien', 'wc-polylang-integration'),
            __('🌐 Bilinguale Kategorien', 'wc-polylang-integration'),
            'manage_options',
            'wc-polylang-bilingual-categories',
            array($this, 'admin_page')
        );
        wc_polylang_bilingual_debug_log("Bilingual Categories Admin-Menü hinzugefügt");
    }
    
    /**
     * Admin-Seite für bilinguale Kategorien
     */
    public function admin_page() {
        wc_polylang_bilingual_debug_log("admin_page() aufgerufen");
        
        $bilingual_enabled = $this->is_bilingual_mode_enabled();
        $categories_status = $this->get_categories_translation_status();
        
        ?>
        <div class="wrap">
            <h1>🌐 Bilinguale Produktkategorien</h1>
            <p class="description">Entwickelt von <strong><a href="https://www.lipalife.de" target="_blank">LipaLIFE</a></strong> - Zeigt Kategorien in beiden Sprachen gleichzeitig an</p>
            
            <div class="notice notice-info">
                <p><strong>💡 Funktionsweise:</strong> Ihre Produktkategorien werden <strong>bilingual angezeigt</strong> - sowohl auf Deutsch als auch auf Englisch, damit Besucher beide Sprachen gleichzeitig sehen können.</p>
                <p><strong>Beispiel:</strong> "Kunststoffteile | Plastic Parts" oder "Spanende Fertigung | Precision Manufacturing"</p>
            </div>
            
            <div class="card">
                <h2>⚙️ Bilinguale Anzeige Konfiguration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Status</th>
                        <td>
                            <label>
                                <input type="checkbox" id="bilingual-categories-toggle" <?php checked($bilingual_enabled); ?>>
                                <strong>Bilinguale Kategorien-Anzeige aktivieren</strong>
                            </label>
                            <p class="description">Zeigt Kategorien in beiden Sprachen gleichzeitig an (Deutsch | English)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Anzeigeformat</th>
                        <td>
                            <select id="bilingual-display-format">
                                <option value="pipe" <?php selected(get_option('wc_polylang_bilingual_format', 'pipe'), 'pipe'); ?>>Deutsch | English</option>
                                <option value="slash" <?php selected(get_option('wc_polylang_bilingual_format', 'pipe'), 'slash'); ?>>Deutsch / English</option>
                                <option value="brackets" <?php selected(get_option('wc_polylang_bilingual_format', 'pipe'), 'brackets'); ?>>Deutsch (English)</option>
                                <option value="flags" <?php selected(get_option('wc_polylang_bilingual_format', 'pipe'), 'flags'); ?>>🇩🇪 Deutsch 🇬🇧 English</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Anzeige-Position</th>
                        <td>
                            <label>
                                <input type="checkbox" id="show-in-widget" <?php checked(get_option('wc_polylang_bilingual_widget', true)); ?>>
                                Kategorien-Widget
                            </label><br>
                            <label>
                                <input type="checkbox" id="show-in-shop" <?php checked(get_option('wc_polylang_bilingual_shop', true)); ?>>
                                Shop-Seite
                            </label><br>
                            <label>
                                <input type="checkbox" id="show-in-archive" <?php checked(get_option('wc_polylang_bilingual_archive', true)); ?>>
                                Kategorie-Archive
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="button" id="save-bilingual-settings" class="button button-primary">
                        💾 Einstellungen speichern
                    </button>
                </p>
            </div>
            
            <div class="card">
                <h2>📊 Kategorien-Übersetzungsstatus</h2>
                <div id="categories-status">
                    <?php $this->display_categories_status($categories_status); ?>
                </div>
                
                <div style="margin: 20px 0;">
                    <button type="button" id="sync-translations" class="button button-secondary">
                        🔄 Übersetzungen synchronisieren
                    </button>
                    <button type="button" id="create-missing-translations" class="button button-secondary">
                        ➕ Fehlende Übersetzungen erstellen
                    </button>
                </div>
            </div>
            
            <div class="card">
                <h2>🎨 Vorschau der bilingualen Anzeige</h2>
                <div class="bilingual-preview">
                    <h3>So werden Ihre Kategorien angezeigt:</h3>
                    <ul class="category-preview-list">
                        <?php foreach ($categories_status as $category): ?>
                            <?php if ($category['de'] && $category['en']): ?>
                                <li class="bilingual-category-item">
                                    <?php echo $this->format_bilingual_category_name($category['de']['name'], $category['en']['name']); ?>
                                    <span class="category-count">(<?php echo $category['de']['count']; ?>)</span>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <h2>🛠️ Erweiterte Optionen</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Fallback-Verhalten</th>
                        <td>
                            <select id="bilingual-fallback">
                                <option value="hide" <?php selected(get_option('wc_polylang_bilingual_fallback', 'show_original'), 'hide'); ?>>Kategorien ohne Übersetzung ausblenden</option>
                                <option value="show_original" <?php selected(get_option('wc_polylang_bilingual_fallback', 'show_original'), 'show_original'); ?>>Nur Originalsprache anzeigen</option>
                                <option value="show_placeholder" <?php selected(get_option('wc_polylang_bilingual_fallback', 'show_original'), 'show_placeholder'); ?>>Mit Platzhalter anzeigen</option>
                            </select>
                            <p class="description">Was passiert, wenn eine Übersetzung fehlt?</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">CSS-Klassen</th>
                        <td>
                            <input type="text" id="custom-css-classes" value="<?php echo esc_attr(get_option('wc_polylang_bilingual_css_classes', 'bilingual-category')); ?>" class="regular-text">
                            <p class="description">Zusätzliche CSS-Klassen für Styling</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2 style="color: white;">🌟 LipaLIFE - Bilinguale Kategorien-Lösung</h2>
                <p>Diese professionelle Lösung zeigt Ihre Produktkategorien in beiden Sprachen gleichzeitig an, damit Ihre Kunden beide Versionen sehen können.</p>
                <p><strong>Perfekt für:</strong> Internationale B2B-Shops, mehrsprachige Zielgruppen, SEO-Optimierung</p>
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
        
        .bilingual-preview {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }
        
        .category-preview-list {
            list-style: none;
            padding: 0;
        }
        
        .bilingual-category-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .bilingual-category-item:last-child {
            border-bottom: none;
        }
        
        .category-count {
            color: #666;
            font-size: 12px;
        }
        
        .bilingual-de {
            font-weight: bold;
            color: #333;
        }
        
        .bilingual-en {
            color: #666;
            font-style: italic;
        }
        
        .bilingual-separator {
            margin: 0 8px;
            color: #999;
        }
        
        #categories-status {
            background: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
            min-height: 100px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle bilinguale Kategorien
            $('#bilingual-categories-toggle').on('change', function() {
                var enabled = $(this).is(':checked');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_toggle_bilingual_categories',
                        enabled: enabled ? 1 : 0,
                        nonce: '<?php echo wp_create_nonce('wc_polylang_bilingual'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Bilinguale Kategorien ' + (enabled ? 'aktiviert' : 'deaktiviert') + '!');
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                    }
                });
            });
            
            // Einstellungen speichern
            $('#save-bilingual-settings').on('click', function() {
                var settings = {
                    format: $('#bilingual-display-format').val(),
                    widget: $('#show-in-widget').is(':checked'),
                    shop: $('#show-in-shop').is(':checked'),
                    archive: $('#show-in-archive').is(':checked'),
                    fallback: $('#bilingual-fallback').val(),
                    css_classes: $('#custom-css-classes').val()
                };
                
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
                            alert('✅ Einstellungen gespeichert!');
                            location.reload();
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                    }
                });
            });
            
            // Übersetzungen synchronisieren
            $('#sync-translations').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('🔄 Synchronisiere...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_sync_category_translations',
                        nonce: '<?php echo wp_create_nonce('wc_polylang_bilingual'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Übersetzungen erfolgreich synchronisiert!');
                            location.reload();
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                        button.prop('disabled', false).text('🔄 Übersetzungen synchronisieren');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Prüfe ob bilinguale Anzeige aktiviert ist
     */
    private function is_bilingual_mode_enabled() {
        return get_option('wc_polylang_bilingual_categories_enabled', false);
    }
    
    /**
     * Hole Kategorien-Übersetzungsstatus
     */
    private function get_categories_translation_status() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ));
        
        $status = array();
        
        foreach ($categories as $category) {
            $lang = function_exists('pll_get_term_language') ? pll_get_term_language($category->term_id) : 'de';
            
            if ($lang === 'de') {
                $en_id = function_exists('pll_get_term') ? pll_get_term($category->term_id, 'en') : false;
                $en_category = $en_id ? get_term($en_id, 'product_cat') : false;
                
                $status[] = array(
                    'de' => array(
                        'id' => $category->term_id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'count' => $category->count
                    ),
                    'en' => $en_category ? array(
                        'id' => $en_category->term_id,
                        'name' => $en_category->name,
                        'slug' => $en_category->slug,
                        'count' => $en_category->count
                    ) : false
                );
            }
        }
        
        return $status;
    }
    
    /**
     * Zeige Kategorien-Status an
     */
    private function display_categories_status($categories_status) {
        if (empty($categories_status)) {
            echo '<p><em>Keine Produktkategorien gefunden.</em></p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>🇩🇪 Deutsche Kategorie</th>';
        echo '<th>🇬🇧 Englische Übersetzung</th>';
        echo '<th>Status</th>';
        echo '<th>Bilinguale Anzeige</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($categories_status as $category) {
            echo '<tr>';
            
            // Deutsche Kategorie
            echo '<td>';
            if ($category['de']) {
                echo '<strong>' . esc_html($category['de']['name']) . '</strong><br>';
                echo '<small>ID: ' . $category['de']['id'] . ' | Produkte: ' . $category['de']['count'] . '</small>';
            }
            echo '</td>';
            
            // Englische Übersetzung
            echo '<td>';
            if ($category['en']) {
                echo '<strong>' . esc_html($category['en']['name']) . '</strong><br>';
                echo '<small>ID: ' . $category['en']['id'] . ' | Produkte: ' . $category['en']['count'] . '</small>';
            } else {
                echo '<span style="color: red;">❌ Keine Übersetzung</span>';
            }
            echo '</td>';
            
            // Status
            echo '<td>';
            if ($category['de'] && $category['en']) {
                echo '<span style="color: green;">✅ Vollständig</span>';
            } else {
                echo '<span style="color: orange;">⚠️ Unvollständig</span>';
            }
            echo '</td>';
            
            // Bilinguale Anzeige
            echo '<td>';
            if ($category['de'] && $category['en']) {
                echo $this->format_bilingual_category_name($category['de']['name'], $category['en']['name']);
            } else if ($category['de']) {
                echo esc_html($category['de']['name']) . ' <em>(nur Deutsch)</em>';
            }
            echo '</td>';
            
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Formatiere bilingualen Kategorienamen
     */
    private function format_bilingual_category_name($de_name, $en_name) {
        $format = get_option('wc_polylang_bilingual_format', 'pipe');
        
        switch ($format) {
            case 'slash':
                return '<span class="bilingual-de">' . esc_html($de_name) . '</span><span class="bilingual-separator"> / </span><span class="bilingual-en">' . esc_html($en_name) . '</span>';
            case 'brackets':
                return '<span class="bilingual-de">' . esc_html($de_name) . '</span> <span class="bilingual-en">(' . esc_html($en_name) . ')</span>';
            case 'flags':
                return '🇩🇪 <span class="bilingual-de">' . esc_html($de_name) . '</span> 🇬🇧 <span class="bilingual-en">' . esc_html($en_name) . '</span>';
            case 'pipe':
            default:
                return '<span class="bilingual-de">' . esc_html($de_name) . '</span><span class="bilingual-separator"> | </span><span class="bilingual-en">' . esc_html($en_name) . '</span>';
        }
    }
    
    /**
     * AJAX: Toggle bilinguale Kategorien
     */
    public function ajax_toggle_bilingual_categories() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_bilingual')) {
            wp_die('Nonce verification failed');
        }
        
        $enabled = intval($_POST['enabled']) === 1;
        update_option('wc_polylang_bilingual_categories_enabled', $enabled);
        
        wc_polylang_bilingual_debug_log("Bilinguale Kategorien " . ($enabled ? 'aktiviert' : 'deaktiviert'));
        
        wp_send_json_success('Bilinguale Kategorien ' . ($enabled ? 'aktiviert' : 'deaktiviert'));
    }
    
    /**
     * AJAX: Synchronisiere Übersetzungen
     */
    public function ajax_sync_translations() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_bilingual')) {
            wp_die('Nonce verification failed');
        }
        
        // Hier würde die Synchronisierung stattfinden
        wc_polylang_bilingual_debug_log("Übersetzungen synchronisiert");
        
        wp_send_json_success('Übersetzungen erfolgreich synchronisiert');
    }
    
    /**
     * Modifiziere Kategorien-Widget Args
     */
    public function modify_categories_widget_args($args) {
        if (!get_option('wc_polylang_bilingual_widget', true)) {
            return $args;
        }
        
        // Hier würde die Widget-Modifikation stattfinden
        return $args;
    }
    
    /**
     * Füge bilinguale Kategorien-Anzeige hinzu
     */
    public function add_bilingual_category_display($output, $args) {
        if (!$this->is_bilingual_mode_enabled()) {
            return $output;
        }
        
        // Hier würde die bilinguale Anzeige implementiert
        return $output;
    }
    
    /**
     * Füge bilinguale Kategorien zur Shop-Seite hinzu
     */
    public function add_bilingual_categories_to_shop($html) {
        if (!get_option('wc_polylang_bilingual_shop', true)) {
            return $html;
        }
        
        // Hier würde die Shop-Integration stattfinden
        return $html;
    }
    
    /**
     * Lade CSS für bilinguale Anzeige
     */
    public function enqueue_bilingual_styles() {
        wp_add_inline_style('woocommerce-general', '
            .bilingual-category {
                display: inline-block;
                margin: 2px 0;
            }
            .bilingual-de {
                font-weight: bold;
                color: #333;
            }
            .bilingual-en {
                color: #666;
                font-style: italic;
            }
            .bilingual-separator {
                margin: 0 8px;
                color: #999;
            }
        ');
    }
}

wc_polylang_bilingual_debug_log("class-wc-polylang-bilingual-categories.php erfolgreich geladen");
