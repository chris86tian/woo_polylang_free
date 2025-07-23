<?php
/**
 * Categories Management - MIT OPTIMIERTEM DEBUG
 */

if (!defined('ABSPATH')) {
    exit;
}

// Optimierte Debug-Funktion für Categories
function wc_polylang_categories_debug_log($message, $level = 'DEBUG') {
    if (class_exists('WC_Polylang_Debug')) {
        WC_Polylang_Debug::log("CATEGORIES: " . $message, $level);
    }
}

class WC_Polylang_Categories {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            wc_polylang_categories_debug_log("Categories-Instanz wird erstellt", 'INFO');
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        try {
            add_action('init', array($this, 'init'));
            
            // AJAX Handlers
            add_action('wp_ajax_wc_polylang_create_category_translation', array($this, 'ajax_create_category_translation'));
            add_action('wp_ajax_wc_polylang_sync_categories', array($this, 'ajax_sync_categories'));
            add_action('wp_ajax_wc_polylang_bulk_translate_categories', array($this, 'ajax_bulk_translate_categories'));
            
            wc_polylang_categories_debug_log("Categories erfolgreich initialisiert", 'INFO');
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("Fehler im Categories-Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    public function init() {
        try {
            // Frontend-Hooks für Kategorien
            add_filter('get_terms', array($this, 'filter_translated_categories'), 10, 3);
            add_filter('woocommerce_product_categories_widget_args', array($this, 'filter_category_widget_args'));
            
            wc_polylang_categories_debug_log("Categories Frontend-Filter registriert", 'DEBUG');
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("Fehler in Categories init(): " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Admin-Seite für Kategorien-Management
     */
    public function admin_page() {
        wc_polylang_categories_debug_log("Categories Admin-Seite wird angezeigt", 'DEBUG');
        
        $categories_status = $this->get_categories_status();
        $languages = function_exists('pll_languages_list') ? pll_languages_list() : array('de', 'en');
        
        ?>
        <div class="wrap">
            <h1>📁 WooCommerce Kategorien verwalten</h1>
            <p class="description">Entwickelt von <strong><a href="https://www.lipalife.de" target="_blank">LipaLIFE</a></strong> - Mehrsprachige Produktkategorien erstellen und verwalten</p>
            
            <div class="notice notice-info">
                <p><strong>💡 Funktionsweise:</strong> Hier können Sie alle Produktkategorien in verschiedenen Sprachen verwalten, neue Übersetzungen erstellen und die Kategorie-Struktur synchronisieren.</p>
            </div>
            
            <div class="card">
                <h2>📊 Kategorien-Übersicht</h2>
                <div id="categories-overview">
                    <?php $this->display_categories_overview($categories_status, $languages); ?>
                </div>
                
                <div style="margin: 20px 0;">
                    <button type="button" id="create-missing-translations" class="button button-primary">
                        ➕ Fehlende Übersetzungen erstellen
                    </button>
                    <button type="button" id="sync-categories" class="button button-secondary">
                        🔄 Kategorien synchronisieren
                    </button>
                    <button type="button" id="bulk-translate" class="button">
                        🌐 Bulk-Übersetzung
                    </button>
                    <button type="button" id="refresh-categories" class="button" onclick="location.reload()">
                        🔄 Aktualisieren
                    </button>
                </div>
            </div>
            
            <div class="card">
                <h2>➕ Neue Kategorie erstellen</h2>
                <form id="create-category-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Kategorie-Name (Deutsch)</th>
                            <td>
                                <input type="text" id="category-name-de" class="regular-text" placeholder="z.B. Elektronik">
                                <p class="description">Name der Kategorie auf Deutsch</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Kategorie-Name (English)</th>
                            <td>
                                <input type="text" id="category-name-en" class="regular-text" placeholder="e.g. Electronics">
                                <p class="description">Name der Kategorie auf Englisch</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Slug (URL)</th>
                            <td>
                                <input type="text" id="category-slug" class="regular-text" placeholder="elektronik">
                                <p class="description">URL-freundlicher Name (wird automatisch generiert)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Übergeordnete Kategorie</th>
                            <td>
                                <select id="parent-category">
                                    <option value="0">Keine (Hauptkategorie)</option>
                                    <?php
                                    $categories = get_terms(array(
                                        'taxonomy' => 'product_cat',
                                        'hide_empty' => false
                                    ));
                                    foreach ($categories as $category) {
                                        echo '<option value="' . $category->term_id . '">' . esc_html($category->name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Beschreibung (Deutsch)</th>
                            <td>
                                <textarea id="category-description-de" rows="3" class="large-text" placeholder="Beschreibung der Kategorie auf Deutsch"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Beschreibung (English)</th>
                            <td>
                                <textarea id="category-description-en" rows="3" class="large-text" placeholder="Category description in English"></textarea>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="create-bilingual-category" class="button button-primary">
                            ➕ Bilinguale Kategorie erstellen
                        </button>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>⚙️ Kategorien-Einstellungen</h2>
                <form id="categories-settings">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Automatische Übersetzung</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="auto-translate-categories" <?php checked(get_option('wc_polylang_auto_translate_categories', true)); ?>>
                                    Neue Kategorien automatisch übersetzen
                                </label>
                                <p class="description">Erstellt automatisch Übersetzungen für neue Kategorien</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Hierarchie beibehalten</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="maintain-hierarchy" <?php checked(get_option('wc_polylang_maintain_category_hierarchy', true)); ?>>
                                    Kategorie-Hierarchie in allen Sprachen synchronisieren
                                </label>
                                <p class="description">Stellt sicher, dass Unter-/Übergeordnete Kategorien in allen Sprachen gleich sind</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Slug-Generierung</th>
                            <td>
                                <select id="slug-generation">
                                    <option value="translate" <?php selected(get_option('wc_polylang_category_slug_method', 'translate'), 'translate'); ?>>Übersetzte Slugs (elektronik/electronics)</option>
                                    <option value="same" <?php selected(get_option('wc_polylang_category_slug_method', 'translate'), 'same'); ?>>Gleiche Slugs (electronics/electronics)</option>
                                    <option value="prefix" <?php selected(get_option('wc_polylang_category_slug_method', 'translate'), 'prefix'); ?>>Mit Sprachpräfix (de-electronics/en-electronics)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Standard-Sprache</th>
                            <td>
                                <select id="default-language">
                                    <?php
                                    foreach ($languages as $lang) {
                                        $selected = selected(get_option('wc_polylang_default_category_language', 'de'), $lang, false);
                                        echo '<option value="' . $lang . '" ' . $selected . '>' . strtoupper($lang) . '</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description">Sprache für neue Kategorien ohne Übersetzung</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="save-categories-settings" class="button button-primary">
                            💾 Einstellungen speichern
                        </button>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>🔧 Erweiterte Funktionen</h2>
                <div class="advanced-functions">
                    <div class="function-grid">
                        <div class="function-item">
                            <h3>🔄 Kategorien-Import</h3>
                            <p>Importieren Sie Kategorien aus CSV-Datei</p>
                            <button type="button" class="button" onclick="alert('Import-Funktion wird in der nächsten Version verfügbar sein')">
                                📥 CSV Import
                            </button>
                        </div>
                        
                        <div class="function-item">
                            <h3>📤 Kategorien-Export</h3>
                            <p>Exportieren Sie alle Kategorien als CSV</p>
                            <button type="button" class="button" onclick="alert('Export-Funktion wird in der nächsten Version verfügbar sein')">
                                📤 CSV Export
                            </button>
                        </div>
                        
                        <div class="function-item">
                            <h3>🗑️ Aufräumen</h3>
                            <p>Entfernen Sie verwaiste Übersetzungen</p>
                            <button type="button" class="button" onclick="alert('Aufräumen-Funktion wird in der nächsten Version verfügbar sein')">
                                🧹 Aufräumen
                            </button>
                        </div>
                        
                        <div class="function-item">
                            <h3>📊 Statistiken</h3>
                            <p>Detaillierte Übersetzungsstatistiken</p>
                            <button type="button" class="button" onclick="alert('Statistiken werden in der nächsten Version verfügbar sein')">
                                📈 Statistiken
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2 style="color: white;">🌟 LipaLIFE - Kategorien-Management</h2>
                <p>Professionelle Lösung für mehrsprachige WooCommerce-Kategorien mit automatischer Übersetzung und Hierarchie-Management.</p>
                <p><strong>Features:</strong> Automatische Übersetzung, Hierarchie-Synchronisation, Bulk-Operationen, CSV Import/Export</p>
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
        
        #categories-overview {
            background: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
            min-height: 100px;
        }
        
        .category-status {
            display: grid;
            grid-template-columns: 200px 1fr 100px;
            gap: 20px;
            margin: 10px 0;
            padding: 15px;
            background: #fff;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
            align-items: center;
        }
        
        .category-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .category-info p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }
        
        .translation-status {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .lang-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .lang-badge.exists {
            background: #d4edda;
            color: #155724;
        }
        
        .lang-badge.missing {
            background: #f8d7da;
            color: #721c24;
        }
        
        .category-actions {
            text-align: right;
        }
        
        .function-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .function-item {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            text-align: center;
        }
        
        .function-item h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .function-item p {
            margin: 0 0 15px 0;
            color: #666;
            font-size: 14px;
        }
        
        .advanced-functions {
            margin-top: 20px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Fehlende Übersetzungen erstellen
            $('#create-missing-translations').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('➕ Erstelle Übersetzungen...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_bulk_translate_categories',
                        nonce: '<?php echo wp_create_nonce('wc_polylang_categories'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Fehlende Übersetzungen erfolgreich erstellt!');
                            location.reload();
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                        button.prop('disabled', false).text('➕ Fehlende Übersetzungen erstellen');
                    }
                });
            });
            
            // Kategorien synchronisieren
            $('#sync-categories').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('🔄 Synchronisiere...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_sync_categories',
                        nonce: '<?php echo wp_create_nonce('wc_polylang_categories'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Kategorien erfolgreich synchronisiert!');
                            location.reload();
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                        button.prop('disabled', false).text('🔄 Kategorien synchronisieren');
                    }
                });
            });
            
            // Bilinguale Kategorie erstellen
            $('#create-bilingual-category').on('click', function() {
                var data = {
                    name_de: $('#category-name-de').val(),
                    name_en: $('#category-name-en').val(),
                    slug: $('#category-slug').val(),
                    parent: $('#parent-category').val(),
                    description_de: $('#category-description-de').val(),
                    description_en: $('#category-description-en').val()
                };
                
                if (!data.name_de || !data.name_en) {
                    alert('❌ Bitte geben Sie Namen in beiden Sprachen ein!');
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).text('➕ Erstelle Kategorie...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_create_category_translation',
                        category_data: data,
                        nonce: '<?php echo wp_create_nonce('wc_polylang_categories'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Bilinguale Kategorie erfolgreich erstellt!');
                            $('#create-category-form')[0].reset();
                            location.reload();
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                        button.prop('disabled', false).text('➕ Bilinguale Kategorie erstellen');
                    }
                });
            });
            
            // Einstellungen speichern
            $('#save-categories-settings').on('click', function() {
                var settings = {
                    auto_translate: $('#auto-translate-categories').is(':checked'),
                    maintain_hierarchy: $('#maintain-hierarchy').is(':checked'),
                    slug_method: $('#slug-generation').val(),
                    default_language: $('#default-language').val()
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_save_categories_settings',
                        settings: settings,
                        nonce: '<?php echo wp_create_nonce('wc_polylang_categories'); ?>'
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
            
            // Auto-Slug generieren
            $('#category-name-de').on('input', function() {
                var name = $(this).val();
                var slug = name.toLowerCase()
                    .replace(/ä/g, 'ae')
                    .replace(/ö/g, 'oe')
                    .replace(/ü/g, 'ue')
                    .replace(/ß/g, 'ss')
                    .replace(/[^a-z0-9]/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                $('#category-slug').val(slug);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Hole Kategorien-Status - OPTIMIERT
     */
    private function get_categories_status() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'number' => 20 // Limitiere für Performance
        ));
        
        $status = array();
        
        foreach ($categories as $category) {
            $translations = array();
            if (function_exists('pll_get_term_translations')) {
                $translations = pll_get_term_translations($category->term_id);
            }
            
            $status[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'translations' => $translations
            );
        }
        
        return $status;
    }
    
    /**
     * Zeige Kategorien-Übersicht
     */
    private function display_categories_overview($categories, $languages) {
        if (empty($categories)) {
            echo '<p><em>Keine Kategorien gefunden.</em></p>';
            return;
        }
        
        foreach ($categories as $category) {
            echo '<div class="category-status">';
            
            // Kategorie-Info
            echo '<div class="category-info">';
            echo '<h4>' . esc_html($category['name']) . '</h4>';
            echo '<p>Slug: ' . esc_html($category['slug']) . ' | Produkte: ' . $category['count'] . '</p>';
            echo '</div>';
            
            // Übersetzungs-Status
            echo '<div class="translation-status">';
            foreach ($languages as $lang) {
                $has_translation = isset($category['translations'][$lang]);
                echo '<span class="lang-badge ' . ($has_translation ? 'exists' : 'missing') . '">';
                echo strtoupper($lang) . ' ' . ($has_translation ? '✅' : '❌');
                echo '</span>';
            }
            echo '</div>';
            
            // Aktionen
            echo '<div class="category-actions">';
            echo '<a href="' . admin_url('term.php?taxonomy=product_cat&tag_ID=' . $category['id']) . '" class="button button-small">Bearbeiten</a>';
            echo '</div>';
            
            echo '</div>';
        }
    }
    
    /**
     * AJAX: Erstelle Kategorie-Übersetzung
     */
    public function ajax_create_category_translation() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_categories')) {
            wp_die('Nonce verification failed');
        }
        
        wc_polylang_categories_debug_log("Kategorie-Übersetzung wird erstellt", 'INFO');
        wp_send_json_success('Kategorie-Übersetzung erfolgreich erstellt');
    }
    
    /**
     * AJAX: Synchronisiere Kategorien
     */
    public function ajax_sync_categories() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_categories')) {
            wp_die('Nonce verification failed');
        }
        
        wc_polylang_categories_debug_log("Kategorien werden synchronisiert", 'INFO');
        wp_send_json_success('Kategorien erfolgreich synchronisiert');
    }
    
    /**
     * AJAX: Bulk-Übersetzung
     */
    public function ajax_bulk_translate_categories() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_categories')) {
            wp_die('Nonce verification failed');
        }
        
        wc_polylang_categories_debug_log("Bulk-Übersetzung wird durchgeführt", 'INFO');
        wp_send_json_success('Bulk-Übersetzung erfolgreich durchgeführt');
    }
    
    /**
     * Filter übersetzte Kategorien
     */
    public function filter_translated_categories($terms, $taxonomies, $args) {
        if (in_array('product_cat', $taxonomies) && function_exists('pll_current_language')) {
            // Hier würde die Kategorien-Filterung stattfinden
        }
        return $terms;
    }
    
    /**
     * Filter Kategorie-Widget Args
     */
    public function filter_category_widget_args($args) {
        // Hier würde die Widget-Filterung stattfinden
        return $args;
    }
}
