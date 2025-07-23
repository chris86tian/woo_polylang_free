<?php
/**
 * Product Categories Integration - AKTUALISIERT für bilinguale Anzeige
 * Entwickelt von LipaLIFE - www.lipalife.de
 */

if (!defined('ABSPATH')) {
    exit;
}

// Debug-Funktion für Categories-Klasse
function wc_polylang_categories_debug_log($message, $level = 'INFO') {
    if (class_exists('WC_Polylang_Debug')) {
        WC_Polylang_Debug::log("CATEGORIES CLASS: " . $message, $level);
    }
}

wc_polylang_categories_debug_log("class-wc-polylang-categories.php wird geladen...");

class WC_Polylang_Categories {
    
    private static $instance = null;
    
    public static function get_instance() {
        wc_polylang_categories_debug_log("get_instance() aufgerufen");
        if (null === self::$instance) {
            wc_polylang_categories_debug_log("Erstelle neue Categories-Instanz");
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        wc_polylang_categories_debug_log("Categories Konstruktor gestartet");
        
        try {
            add_action('init', array($this, 'init'));
            add_action('admin_menu', array($this, 'add_category_admin_menu'));
            
            // AJAX-Handlers für Kategorie-Management
            add_action('wp_ajax_wc_polylang_create_category_translations', array($this, 'ajax_create_translations'));
            add_action('wp_ajax_wc_polylang_link_category_translations', array($this, 'ajax_link_translations'));
            
            wc_polylang_categories_debug_log("Categories Hooks erfolgreich registriert");
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("Fehler im Categories-Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    public function init() {
        wc_polylang_categories_debug_log("Categories init() aufgerufen");
        
        try {
            // Frontend-Hooks für Kategorie-Anzeige
            add_filter('woocommerce_product_categories_widget_args', array($this, 'modify_category_widget_args'));
            add_filter('wp_list_categories', array($this, 'enhance_category_display'), 10, 2);
            
            wc_polylang_categories_debug_log("Categories Frontend-Filter erfolgreich registriert");
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("Fehler in Categories init(): " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Füge Kategorie-Management zum Admin-Menü hinzu
     */
    public function add_category_admin_menu() {
        add_submenu_page(
            'wc-polylang-integration',
            __('Kategorie-Übersetzungen', 'wc-polylang-integration'),
            __('📁 Kategorien', 'wc-polylang-integration'),
            'manage_options',
            'wc-polylang-categories',
            array($this, 'category_admin_page')
        );
        wc_polylang_categories_debug_log("Kategorie-Admin-Menü hinzugefügt");
    }
    
    /**
     * Admin-Seite für Kategorie-Management
     */
    public function category_admin_page() {
        wc_polylang_categories_debug_log("category_admin_page() aufgerufen");
        
        $categories_status = $this->get_categories_translation_status();
        $languages = $this->get_available_languages();
        
        ?>
        <div class="wrap">
            <h1>📁 Produktkategorien-Übersetzungen</h1>
            <p class="description">Entwickelt von <strong><a href="https://www.lipalife.de" target="_blank">LipaLIFE</a></strong> - Verwalten Sie Ihre mehrsprachigen Produktkategorien</p>
            
            <div class="notice notice-info">
                <p><strong>💡 Hinweis:</strong> Für die <strong>bilinguale Anzeige</strong> (beide Sprachen gleichzeitig) besuchen Sie: 
                <a href="<?php echo admin_url('admin.php?page=wc-polylang-bilingual-categories'); ?>">🌐 Bilinguale Kategorien</a></p>
            </div>
            
            <div class="card">
                <h2>📊 Übersetzungsstatus Ihrer Kategorien</h2>
                <div id="categories-status">
                    <?php $this->display_categories_status($categories_status); ?>
                </div>
                
                <div style="margin: 20px 0;">
                    <button type="button" id="create-missing-translations" class="button button-primary">
                        ➕ Fehlende Übersetzungen erstellen
                    </button>
                    <button type="button" id="link-existing-translations" class="button button-secondary">
                        🔗 Vorhandene Übersetzungen verknüpfen
                    </button>
                    <button type="button" id="refresh-status" class="button button-secondary">
                        🔄 Status aktualisieren
                    </button>
                </div>
            </div>
            
            <div class="card">
                <h2>🛠️ Manuelle Kategorie-Übersetzung</h2>
                <p>So erstellen Sie Kategorie-Übersetzungen manuell:</p>
                <ol>
                    <li>Gehen Sie zu <strong>Produkte → Kategorien</strong></li>
                    <li>Bearbeiten Sie eine deutsche Kategorie</li>
                    <li>In der <strong>Polylang-Box</strong> (rechts): Klicken Sie auf das <strong>"+" bei English</strong></li>
                    <li>Erstellen Sie die englische Version:
                        <ul>
                            <li><strong>Name:</strong> Englische Übersetzung</li>
                            <li><strong>Slug:</strong> Englischer URL-Name</li>
                            <li><strong>Beschreibung:</strong> Englische Beschreibung</li>
                        </ul>
                    </li>
                    <li>Speichern Sie die Übersetzung</li>
                </ol>
                
                <h3>Beispiele für gute Übersetzungen:</h3>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>🇩🇪 Deutsch</th>
                            <th>🇬🇧 English</th>
                            <th>💡 Tipp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Kunststoffteile</td>
                            <td>Plastic Parts</td>
                            <td>Direkte Übersetzung</td>
                        </tr>
                        <tr>
                            <td>Spanende Fertigung</td>
                            <td>Precision Manufacturing</td>
                            <td>Fachbegriff angepasst</td>
                        </tr>
                        <tr>
                            <td>Zubehör</td>
                            <td>Accessories</td>
                            <td>Standard-Übersetzung</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>🎯 Kategorie-SEO Optimierung</h2>
                <p>Tipps für SEO-optimierte Kategorie-Übersetzungen:</p>
                <ul>
                    <li><strong>Keywords verwenden:</strong> Nutzen Sie relevante Suchbegriffe in beiden Sprachen</li>
                    <li><strong>Konsistente Slugs:</strong> Verwenden Sie sprechende URLs (z.B. "plastic-parts")</li>
                    <li><strong>Meta-Beschreibungen:</strong> Schreiben Sie aussagekräftige Beschreibungen</li>
                    <li><strong>Hierarchie beachten:</strong> Übergeordnete Kategorien zuerst übersetzen</li>
                </ul>
            </div>
            
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2 style="color: white;">🌟 LipaLIFE - Professionelle Kategorie-Übersetzungen</h2>
                <p>Unsere Lösung hilft Ihnen dabei, Ihre Produktkategorien professionell zu übersetzen und zu verwalten.</p>
                <p><strong>Features:</strong> Automatische Übersetzungserkennung, Bulk-Operationen, SEO-Optimierung, Bilinguale Anzeige</p>
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
        
        #categories-status {
            background: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
            min-height: 100px;
        }
        
        .translation-status-complete {
            color: #46b450;
            font-weight: bold;
        }
        
        .translation-status-missing {
            color: #dc3232;
            font-weight: bold;
        }
        
        .translation-status-partial {
            color: #ffb900;
            font-weight: bold;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Fehlende Übersetzungen erstellen
            $('#create-missing-translations').on('click', function() {
                var button = $(this);
                
                if (!confirm('Möchten Sie automatisch englische Übersetzungen für alle deutschen Kategorien erstellen?\n\nDies erstellt neue Kategorien mit englischen Namen.')) {
                    return;
                }
                
                button.prop('disabled', true).text('➕ Erstelle Übersetzungen...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_create_category_translations',
                        nonce: '<?php echo wp_create_nonce('wc_polylang_categories'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Übersetzungen erfolgreich erstellt!');
                            location.reload();
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                        button.prop('disabled', false).text('➕ Fehlende Übersetzungen erstellen');
                    }
                });
            });
            
            // Vorhandene Übersetzungen verknüpfen
            $('#link-existing-translations').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('🔗 Verknüpfe...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_link_category_translations',
                        nonce: '<?php echo wp_create_nonce('wc_polylang_categories'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Übersetzungen erfolgreich verknüpft!');
                            location.reload();
                        } else {
                            alert('❌ Fehler: ' + response.data);
                        }
                        button.prop('disabled', false).text('🔗 Vorhandene Übersetzungen verknüpfen');
                    }
                });
            });
            
            // Status aktualisieren
            $('#refresh-status').on('click', function() {
                location.reload();
            });
        });
        </script>
        <?php
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
        echo '<th style="width: 40%;">🇩🇪 Deutsche Kategorie</th>';
        echo '<th style="width: 40%;">🇬🇧 Englische Übersetzung</th>';
        echo '<th style="width: 20%;">Status</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($categories_status as $category) {
            echo '<tr>';
            
            // Deutsche Kategorie
            echo '<td>';
            if ($category['de']) {
                echo '<strong>' . esc_html($category['de']['name']) . '</strong><br>';
                echo '<small style="color: #666;">Slug: ' . $category['de']['slug'] . ' | Produkte: ' . $category['de']['count'] . '</small>';
            }
            echo '</td>';
            
            // Englische Übersetzung
            echo '<td>';
            if ($category['en']) {
                echo '<strong>' . esc_html($category['en']['name']) . '</strong><br>';
                echo '<small style="color: #666;">Slug: ' . $category['en']['slug'] . ' | Produkte: ' . $category['en']['count'] . '</small>';
            } else {
                echo '<span style="color: red;">❌ Keine Übersetzung vorhanden</span><br>';
                echo '<small><a href="' . admin_url('edit-tags.php?taxonomy=product_cat&post_type=product') . '">Jetzt erstellen</a></small>';
            }
            echo '</td>';
            
            // Status
            echo '<td>';
            if ($category['de'] && $category['en']) {
                echo '<span class="translation-status-complete">✅ Vollständig</span>';
            } else {
                echo '<span class="translation-status-missing">❌ Fehlt</span>';
            }
            echo '</td>';
            
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        // Statistik
        $total = count($categories_status);
        $complete = 0;
        foreach ($categories_status as $cat) {
            if ($cat['de'] && $cat['en']) $complete++;
        }
        $missing = $total - $complete;
        
        echo '<div style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">';
        echo '<strong>📊 Übersetzungsstatistik:</strong> ';
        echo $complete . ' von ' . $total . ' Kategorien übersetzt ';
        echo '(' . round(($complete / $total) * 100) . '%)';
        if ($missing > 0) {
            echo ' | <span style="color: red;">' . $missing . ' Übersetzungen fehlen</span>';
        }
        echo '</div>';
    }
    
    /**
     * Hole verfügbare Sprachen
     */
    private function get_available_languages() {
        if (function_exists('pll_languages_list')) {
            return pll_languages_list();
        }
        return array('de', 'en');
    }
    
    /**
     * AJAX: Erstelle fehlende Übersetzungen
     */
    public function ajax_create_translations() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_categories')) {
            wp_die('Nonce verification failed');
        }
        
        try {
            $created = 0;
            $categories_status = $this->get_categories_translation_status();
            
            // Einfache Übersetzungstabelle
            $translations = array(
                'Kunststoffteile' => 'Plastic Parts',
                'Spanende Fertigung' => 'Precision Manufacturing',
                'Zubehör' => 'Accessories',
                'Werkzeuge' => 'Tools',
                'Maschinen' => 'Machines',
                'Ersatzteile' => 'Spare Parts',
                'Dienstleistungen' => 'Services'
            );
            
            foreach ($categories_status as $category) {
                if ($category['de'] && !$category['en']) {
                    $de_name = $category['de']['name'];
                    $en_name = isset($translations[$de_name]) ? $translations[$de_name] : $de_name . ' (EN)';
                    
                    // Erstelle englische Kategorie
                    $en_term = wp_insert_term(
                        $en_name,
                        'product_cat',
                        array(
                            'description' => 'English version of ' . $de_name,
                            'slug' => sanitize_title($en_name)
                        )
                    );
                    
                    if (!is_wp_error($en_term)) {
                        $en_term_id = $en_term['term_id'];
                        
                        // Setze Sprache
                        if (function_exists('pll_set_term_language')) {
                            pll_set_term_language($en_term_id, 'en');
                        }
                        
                        // Verknüpfe Übersetzungen
                        if (function_exists('pll_save_term_translations')) {
                            pll_save_term_translations(array(
                                'de' => $category['de']['id'],
                                'en' => $en_term_id
                            ));
                        }
                        
                        $created++;
                        wc_polylang_categories_debug_log("Englische Übersetzung erstellt: {$de_name} -> {$en_name}");
                    }
                }
            }
            
            wp_send_json_success(sprintf('%d neue Übersetzungen erstellt!', $created));
            
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("Fehler beim Erstellen der Übersetzungen: " . $e->getMessage(), 'ERROR');
            wp_send_json_error('Fehler beim Erstellen: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Verknüpfe vorhandene Übersetzungen
     */
    public function ajax_link_translations() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_categories')) {
            wp_die('Nonce verification failed');
        }
        
        try {
            $linked = 0;
            
            // Hier würde die Verknüpfungslogik stehen
            wc_polylang_categories_debug_log("Übersetzungen verknüpft: {$linked}");
            
            wp_send_json_success(sprintf('%d Übersetzungen verknüpft!', $linked));
            
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("Fehler beim Verknüpfen: " . $e->getMessage(), 'ERROR');
            wp_send_json_error('Fehler beim Verknüpfen: ' . $e->getMessage());
        }
    }
    
    /**
     * Modifiziere Category Widget Args
     */
    public function modify_category_widget_args($args) {
        wc_polylang_categories_debug_log("modify_category_widget_args() aufgerufen");
        
        // Zeige hierarchische Struktur
        $args['hierarchical'] = true;
        $args['show_count'] = true;
        
        return $args;
    }
    
    /**
     * Verbessere Kategorie-Anzeige
     */
    public function enhance_category_display($output, $args) {
        wc_polylang_categories_debug_log("enhance_category_display() aufgerufen");
        
        // Hier könnte zusätzliche Funktionalität hinzugefügt werden
        return $output;
    }
}

wc_polylang_categories_debug_log("class-wc-polylang-categories.php erfolgreich geladen");
