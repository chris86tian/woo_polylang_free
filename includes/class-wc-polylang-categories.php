<?php
/**
 * Hierarchical Multilingual Categories - Option B Implementation
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
            add_action('admin_init', array($this, 'maybe_create_hierarchical_structure'));
            
            // Admin-Hooks für Kategorie-Management
            add_action('admin_menu', array($this, 'add_category_admin_menu'));
            add_action('wp_ajax_create_hierarchical_categories', array($this, 'ajax_create_categories'));
            add_action('wp_ajax_reset_category_structure', array($this, 'ajax_reset_structure'));
            
            wc_polylang_categories_debug_log("Categories Hooks erfolgreich registriert");
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("Fehler im Categories-Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    public function init() {
        wc_polylang_categories_debug_log("Categories init() aufgerufen");
        
        try {
            // Frontend-Hooks
            add_filter('woocommerce_product_categories_widget_args', array($this, 'modify_category_widget_args'));
            add_filter('wp_list_categories', array($this, 'add_language_flags_to_categories'), 10, 2);
            
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
            __('Kategorie-Struktur', 'wc-polylang-integration'),
            __('📁 Kategorien', 'wc-polylang-integration'),
            'manage_woocommerce',
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
        
        $current_structure = $this->get_current_category_structure();
        $languages = $this->get_available_languages();
        
        ?>
        <div class="wrap">
            <h1>📁 Hierarchische Kategorie-Struktur (Option B)</h1>
            <p class="description">Entwickelt von <strong><a href="https://www.lipalife.de" target="_blank">LipaLIFE</a></strong></p>
            
            <div class="card">
                <h2>🎯 Aktuelle Struktur</h2>
                <div id="current-structure">
                    <?php $this->display_current_structure($current_structure); ?>
                </div>
                
                <div style="margin: 20px 0;">
                    <button type="button" class="button button-primary" onclick="createHierarchicalCategories()">
                        🚀 Hierarchische Struktur erstellen
                    </button>
                    <button type="button" class="button button-secondary" onclick="resetCategoryStructure()">
                        🔄 Struktur zurücksetzen
                    </button>
                </div>
            </div>
            
            <div class="card">
                <h2>⚙️ Kategorie-Konfiguration</h2>
                <form id="category-config-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Hauptsprachen</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="languages[]" value="de" checked> 
                                    🇩🇪 Deutsch
                                </label><br>
                                <label>
                                    <input type="checkbox" name="languages[]" value="en" checked> 
                                    🇬🇧 English
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Produktkategorien</th>
                            <td>
                                <div id="category-list">
                                    <div class="category-item">
                                        <input type="text" name="categories[de][]" value="Elektronik" placeholder="Deutsche Kategorie">
                                        <input type="text" name="categories[en][]" value="Electronics" placeholder="English Category">
                                        <button type="button" onclick="removeCategoryItem(this)">❌</button>
                                    </div>
                                    <div class="category-item">
                                        <input type="text" name="categories[de][]" value="Kleidung" placeholder="Deutsche Kategorie">
                                        <input type="text" name="categories[en][]" value="Clothing" placeholder="English Category">
                                        <button type="button" onclick="removeCategoryItem(this)">❌</button>
                                    </div>
                                    <div class="category-item">
                                        <input type="text" name="categories[de][]" value="Bücher" placeholder="Deutsche Kategorie">
                                        <input type="text" name="categories[en][]" value="Books" placeholder="English Category">
                                        <button type="button" onclick="removeCategoryItem(this)">❌</button>
                                    </div>
                                </div>
                                <button type="button" onclick="addCategoryItem()">➕ Kategorie hinzufügen</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            
            <div class="card">
                <h2>📊 Vorschau der Struktur</h2>
                <div class="structure-preview">
                    <ul class="category-tree">
                        <li>📁 <strong>🇩🇪 Deutsch</strong>
                            <ul>
                                <li>📦 Elektronik</li>
                                <li>👕 Kleidung</li>
                                <li>📚 Bücher</li>
                            </ul>
                        </li>
                        <li>📁 <strong>🇬🇧 English</strong>
                            <ul>
                                <li>📦 Electronics</li>
                                <li>👕 Clothing</li>
                                <li>📚 Books</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2 style="color: white;">🌟 LipaLIFE - Hierarchische Kategorie-Lösung</h2>
                <p>Diese professionelle Lösung erstellt automatisch eine hierarchische Kategorie-Struktur für Ihren mehrsprachigen WooCommerce-Shop.</p>
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
        .category-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .category-item input[type="text"] {
            flex: 1;
            padding: 8px;
        }
        .category-tree {
            font-family: monospace;
            font-size: 14px;
            line-height: 1.6;
        }
        .category-tree ul {
            margin-left: 20px;
        }
        .structure-preview {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }
        #current-structure {
            background: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
            min-height: 100px;
        }
        </style>
        
        <script>
        function createHierarchicalCategories() {
            if (!confirm('Möchten Sie die hierarchische Kategorie-Struktur erstellen? Dies kann nicht rückgängig gemacht werden.')) {
                return;
            }
            
            const formData = new FormData(document.getElementById('category-config-form'));
            formData.append('action', 'create_hierarchical_categories');
            formData.append('nonce', '<?php echo wp_create_nonce('wc_polylang_categories'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Hierarchische Kategorie-Struktur erfolgreich erstellt!');
                    location.reload();
                } else {
                    alert('❌ Fehler: ' + data.data);
                }
            })
            .catch(error => {
                alert('❌ Fehler beim Erstellen der Struktur: ' + error);
            });
        }
        
        function resetCategoryStructure() {
            if (!confirm('Möchten Sie wirklich die gesamte Kategorie-Struktur zurücksetzen? ACHTUNG: Dies löscht alle Kategorien!')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'reset_category_structure');
            formData.append('nonce', '<?php echo wp_create_nonce('wc_polylang_categories'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Kategorie-Struktur erfolgreich zurückgesetzt!');
                    location.reload();
                } else {
                    alert('❌ Fehler: ' + data.data);
                }
            });
        }
        
        function addCategoryItem() {
            const categoryList = document.getElementById('category-list');
            const newItem = document.createElement('div');
            newItem.className = 'category-item';
            newItem.innerHTML = `
                <input type="text" name="categories[de][]" placeholder="Deutsche Kategorie">
                <input type="text" name="categories[en][]" placeholder="English Category">
                <button type="button" onclick="removeCategoryItem(this)">❌</button>
            `;
            categoryList.appendChild(newItem);
        }
        
        function removeCategoryItem(button) {
            button.parentElement.remove();
        }
        </script>
        <?php
    }
    
    /**
     * AJAX: Erstelle hierarchische Kategorie-Struktur
     */
    public function ajax_create_categories() {
        wc_polylang_categories_debug_log("ajax_create_categories() aufgerufen");
        
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_categories')) {
            wp_die('Nonce verification failed');
        }
        
        try {
            $languages = isset($_POST['languages']) ? $_POST['languages'] : array('de', 'en');
            $categories = isset($_POST['categories']) ? $_POST['categories'] : array();
            
            wc_polylang_categories_debug_log("Erstelle Struktur für Sprachen: " . implode(', ', $languages));
            
            $result = $this->create_hierarchical_structure($languages, $categories);
            
            if ($result['success']) {
                wc_polylang_categories_debug_log("Hierarchische Struktur erfolgreich erstellt");
                wp_send_json_success($result['message']);
            } else {
                wc_polylang_categories_debug_log("Fehler beim Erstellen: " . $result['message'], 'ERROR');
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("AJAX Fehler: " . $e->getMessage(), 'ERROR');
            wp_send_json_error('Fehler beim Erstellen der Struktur: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Setze Kategorie-Struktur zurück
     */
    public function ajax_reset_structure() {
        wc_polylang_categories_debug_log("ajax_reset_structure() aufgerufen");
        
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_categories')) {
            wp_die('Nonce verification failed');
        }
        
        try {
            $result = $this->reset_category_structure();
            
            if ($result['success']) {
                wc_polylang_categories_debug_log("Kategorie-Struktur erfolgreich zurückgesetzt");
                wp_send_json_success($result['message']);
            } else {
                wc_polylang_categories_debug_log("Fehler beim Zurücksetzen: " . $result['message'], 'ERROR');
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("AJAX Reset Fehler: " . $e->getMessage(), 'ERROR');
            wp_send_json_error('Fehler beim Zurücksetzen: ' . $e->getMessage());
        }
    }
    
    /**
     * Erstelle die hierarchische Kategorie-Struktur
     */
    public function create_hierarchical_structure($languages, $categories) {
        wc_polylang_categories_debug_log("create_hierarchical_structure() gestartet");
        
        try {
            $created_categories = array();
            $language_names = array(
                'de' => 'Deutsch',
                'en' => 'English'
            );
            
            // 1. Erstelle Hauptsprach-Kategorien
            foreach ($languages as $lang) {
                $lang_name = isset($language_names[$lang]) ? $language_names[$lang] : ucfirst($lang);
                $flag = $lang === 'de' ? '🇩🇪' : '🇬🇧';
                
                wc_polylang_categories_debug_log("Erstelle Hauptkategorie für Sprache: {$lang}");
                
                $parent_term = wp_insert_term(
                    $flag . ' ' . $lang_name,
                    'product_cat',
                    array(
                        'description' => sprintf(__('Hauptkategorie für %s Produkte', 'wc-polylang-integration'), $lang_name),
                        'slug' => 'lang-' . $lang
                    )
                );
                
                if (is_wp_error($parent_term)) {
                    wc_polylang_categories_debug_log("Fehler beim Erstellen der Hauptkategorie {$lang}: " . $parent_term->get_error_message(), 'ERROR');
                    continue;
                }
                
                $parent_id = $parent_term['term_id'];
                $created_categories[$lang]['parent'] = $parent_id;
                
                // Setze Polylang-Sprache für Hauptkategorie
                if (function_exists('pll_set_term_language')) {
                    pll_set_term_language($parent_id, $lang);
                    wc_polylang_categories_debug_log("Polylang-Sprache {$lang} für Hauptkategorie gesetzt");
                }
                
                // 2. Erstelle Unterkategorien
                if (isset($categories[$lang]) && is_array($categories[$lang])) {
                    foreach ($categories[$lang] as $index => $category_name) {
                        if (empty(trim($category_name))) continue;
                        
                        wc_polylang_categories_debug_log("Erstelle Unterkategorie: {$category_name} unter {$lang}");
                        
                        $child_term = wp_insert_term(
                            trim($category_name),
                            'product_cat',
                            array(
                                'description' => sprintf(__('%s Kategorie in %s', 'wc-polylang-integration'), $category_name, $lang_name),
                                'slug' => sanitize_title($category_name . '-' . $lang),
                                'parent' => $parent_id
                            )
                        );
                        
                        if (is_wp_error($child_term)) {
                            wc_polylang_categories_debug_log("Fehler beim Erstellen der Unterkategorie {$category_name}: " . $child_term->get_error_message(), 'ERROR');
                            continue;
                        }
                        
                        $child_id = $child_term['term_id'];
                        $created_categories[$lang]['children'][] = $child_id;
                        
                        // Setze Polylang-Sprache für Unterkategorie
                        if (function_exists('pll_set_term_language')) {
                            pll_set_term_language($child_id, $lang);
                            wc_polylang_categories_debug_log("Polylang-Sprache {$lang} für Unterkategorie {$category_name} gesetzt");
                        }
                    }
                }
            }
            
            // 3. Verknüpfe übersetzbare Kategorien
            $this->link_translated_categories($created_categories, $categories);
            
            // 4. Speichere Struktur-Info
            update_option('wc_polylang_hierarchical_structure', $created_categories);
            update_option('wc_polylang_structure_created', time());
            
            wc_polylang_categories_debug_log("Hierarchische Struktur erfolgreich erstellt: " . json_encode($created_categories));
            
            return array(
                'success' => true,
                'message' => sprintf(__('Hierarchische Struktur mit %d Sprachen erfolgreich erstellt!', 'wc-polylang-integration'), count($languages)),
                'data' => $created_categories
            );
            
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("Fehler beim Erstellen der hierarchischen Struktur: " . $e->getMessage(), 'ERROR');
            return array(
                'success' => false,
                'message' => 'Fehler beim Erstellen: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Verknüpfe übersetzbare Kategorien miteinander
     */
    private function link_translated_categories($created_categories, $categories) {
        wc_polylang_categories_debug_log("link_translated_categories() gestartet");
        
        if (!function_exists('pll_save_term_translations')) {
            wc_polylang_categories_debug_log("pll_save_term_translations() nicht verfügbar", 'WARNING');
            return;
        }
        
        try {
            // Verknüpfe Hauptkategorien
            $main_translations = array();
            foreach ($created_categories as $lang => $data) {
                if (isset($data['parent'])) {
                    $main_translations[$lang] = $data['parent'];
                }
            }
            
            if (count($main_translations) > 1) {
                pll_save_term_translations($main_translations);
                wc_polylang_categories_debug_log("Hauptkategorien verknüpft: " . json_encode($main_translations));
            }
            
            // Verknüpfe Unterkategorien (gleicher Index = Übersetzung)
            $max_children = 0;
            foreach ($created_categories as $lang => $data) {
                if (isset($data['children'])) {
                    $max_children = max($max_children, count($data['children']));
                }
            }
            
            for ($i = 0; $i < $max_children; $i++) {
                $child_translations = array();
                foreach ($created_categories as $lang => $data) {
                    if (isset($data['children'][$i])) {
                        $child_translations[$lang] = $data['children'][$i];
                    }
                }
                
                if (count($child_translations) > 1) {
                    pll_save_term_translations($child_translations);
                    wc_polylang_categories_debug_log("Unterkategorien Index {$i} verknüpft: " . json_encode($child_translations));
                }
            }
            
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("Fehler beim Verknüpfen der Kategorien: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Setze Kategorie-Struktur zurück
     */
    public function reset_category_structure() {
        wc_polylang_categories_debug_log("reset_category_structure() gestartet");
        
        try {
            $structure = get_option('wc_polylang_hierarchical_structure', array());
            $deleted_count = 0;
            
            foreach ($structure as $lang => $data) {
                // Lösche Unterkategorien zuerst
                if (isset($data['children']) && is_array($data['children'])) {
                    foreach ($data['children'] as $child_id) {
                        $result = wp_delete_term($child_id, 'product_cat');
                        if (!is_wp_error($result)) {
                            $deleted_count++;
                            wc_polylang_categories_debug_log("Unterkategorie {$child_id} gelöscht");
                        }
                    }
                }
                
                // Lösche Hauptkategorie
                if (isset($data['parent'])) {
                    $result = wp_delete_term($data['parent'], 'product_cat');
                    if (!is_wp_error($result)) {
                        $deleted_count++;
                        wc_polylang_categories_debug_log("Hauptkategorie {$data['parent']} gelöscht");
                    }
                }
            }
            
            // Lösche gespeicherte Struktur-Info
            delete_option('wc_polylang_hierarchical_structure');
            delete_option('wc_polylang_structure_created');
            
            wc_polylang_categories_debug_log("Struktur zurückgesetzt - {$deleted_count} Kategorien gelöscht");
            
            return array(
                'success' => true,
                'message' => sprintf(__('%d Kategorien erfolgreich gelöscht!', 'wc-polylang-integration'), $deleted_count)
            );
            
        } catch (Exception $e) {
            wc_polylang_categories_debug_log("Fehler beim Zurücksetzen: " . $e->getMessage(), 'ERROR');
            return array(
                'success' => false,
                'message' => 'Fehler beim Zurücksetzen: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Zeige aktuelle Kategorie-Struktur an
     */
    private function display_current_structure($structure) {
        if (empty($structure)) {
            echo '<p><em>Noch keine hierarchische Struktur erstellt.</em></p>';
            return;
        }
        
        echo '<ul class="category-tree">';
        foreach ($structure as $lang => $data) {
            $flag = $lang === 'de' ? '🇩🇪' : '🇬🇧';
            $lang_name = $lang === 'de' ? 'Deutsch' : 'English';
            
            if (isset($data['parent'])) {
                $parent_term = get_term($data['parent'], 'product_cat');
                if ($parent_term && !is_wp_error($parent_term)) {
                    echo '<li>📁 <strong>' . esc_html($parent_term->name) . '</strong> (' . $parent_term->count . ' Produkte)';
                    
                    if (isset($data['children']) && is_array($data['children'])) {
                        echo '<ul>';
                        foreach ($data['children'] as $child_id) {
                            $child_term = get_term($child_id, 'product_cat');
                            if ($child_term && !is_wp_error($child_term)) {
                                echo '<li>📦 ' . esc_html($child_term->name) . ' (' . $child_term->count . ' Produkte)</li>';
                            }
                        }
                        echo '</ul>';
                    }
                    echo '</li>';
                }
            }
        }
        echo '</ul>';
    }
    
    /**
     * Hole aktuelle Kategorie-Struktur
     */
    private function get_current_category_structure() {
        return get_option('wc_polylang_hierarchical_structure', array());
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
     * Automatische Erstellung bei Plugin-Aktivierung (optional)
     */
    public function maybe_create_hierarchical_structure() {
        $auto_create = get_option('wc_polylang_auto_create_structure', false);
        $structure_exists = get_option('wc_polylang_structure_created', false);
        
        if ($auto_create && !$structure_exists) {
            wc_polylang_categories_debug_log("Automatische Struktur-Erstellung gestartet");
            
            $default_categories = array(
                'de' => array('Elektronik', 'Kleidung', 'Bücher'),
                'en' => array('Electronics', 'Clothing', 'Books')
            );
            
            $this->create_hierarchical_structure(array('de', 'en'), $default_categories);
        }
    }
    
    /**
     * Modifiziere Category Widget Args für hierarchische Anzeige
     */
    public function modify_category_widget_args($args) {
        wc_polylang_categories_debug_log("modify_category_widget_args() aufgerufen");
        
        // Zeige hierarchische Struktur im Widget
        $args['hierarchical'] = true;
        $args['show_count'] = true;
        
        return $args;
    }
    
    /**
     * Füge Sprach-Flags zu Kategorien hinzu
     */
    public function add_language_flags_to_categories($output, $args) {
        wc_polylang_categories_debug_log("add_language_flags_to_categories() aufgerufen");
        
        // Füge Flags basierend auf Kategorie-Namen hinzu
        $output = str_replace('🇩🇪 Deutsch', '<span class="lang-flag">🇩🇪</span> Deutsch', $output);
        $output = str_replace('🇬🇧 English', '<span class="lang-flag">🇬🇧</span> English', $output);
        
        return $output;
    }
}

wc_polylang_categories_debug_log("class-wc-polylang-categories.php erfolgreich geladen");
