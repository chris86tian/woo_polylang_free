<?php
/**
 * Elementor Pro Integration - MIT HIERARCHICAL CATEGORY SUPPORT
 * Entwickelt von LipaLIFE - www.lipalife.de
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
            add_action('init', array($this, 'init'));
            add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
            
            // Template-Hooks für hierarchische Kategorien
            add_action('elementor/template-library/after_save_template', array($this, 'duplicate_template_for_languages'));
            add_filter('elementor/frontend/builder_content_data', array($this, 'modify_category_widgets_content'), 10, 2);
            
            wc_polylang_elementor_debug_log("Elementor Hooks erfolgreich registriert");
        } catch (Exception $e) {
            wc_polylang_elementor_debug_log("Fehler im Elementor-Konstruktor: " . $e->getMessage(), 'ERROR');
        }
    }
    
    public function init() {
        wc_polylang_elementor_debug_log("Elementor init() aufgerufen");
        
        if (!defined('ELEMENTOR_VERSION')) {
            wc_polylang_elementor_debug_log("Elementor nicht gefunden", 'WARNING');
            return;
        }
        
        try {
            // Elementor Pro spezifische Hooks
            if (defined('ELEMENTOR_PRO_VERSION')) {
                wc_polylang_elementor_debug_log("Elementor Pro gefunden - Version: " . ELEMENTOR_PRO_VERSION);
                
                // WooCommerce Widgets erweitern
                add_action('elementor/element/woocommerce-categories/section_layout/before_section_end', array($this, 'add_language_controls'));
                add_action('elementor/element/woocommerce-product-categories/section_layout/before_section_end', array($this, 'add_language_controls'));
                
                // Template-Duplikation für Kategorien
                add_action('wp_ajax_duplicate_category_template', array($this, 'ajax_duplicate_template'));
                
            } else {
                wc_polylang_elementor_debug_log("Elementor Pro nicht gefunden - nur Basis-Integration", 'WARNING');
            }
            
            wc_polylang_elementor_debug_log("Elementor Integration erfolgreich initialisiert");
        } catch (Exception $e) {
            wc_polylang_elementor_debug_log("Fehler in Elementor init(): " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Registriere Custom Widgets
     */
    public function register_widgets() {
        wc_polylang_elementor_debug_log("register_widgets() aufgerufen");
        
        if (!defined('ELEMENTOR_PRO_VERSION')) {
            return;
        }
        
        try {
            // Custom Hierarchical Category Widget
            require_once WC_POLYLANG_INTEGRATION_PLUGIN_DIR . 'includes/elementor-widgets/hierarchical-categories-widget.php';
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \WC_Polylang_Hierarchical_Categories_Widget());
            
            wc_polylang_elementor_debug_log("Custom Widgets erfolgreich registriert");
        } catch (Exception $e) {
            wc_polylang_elementor_debug_log("Fehler beim Registrieren der Widgets: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Füge Sprach-Controls zu WooCommerce Widgets hinzu
     */
    public function add_language_controls($element) {
        wc_polylang_elementor_debug_log("add_language_controls() aufgerufen");
        
        $element->add_control(
            'wc_polylang_language_filter',
            array(
                'label' => __('Sprach-Filter', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'all' => __('Alle Sprachen', 'wc-polylang-integration'),
                    'current' => __('Aktuelle Sprache', 'wc-polylang-integration'),
                    'de' => '🇩🇪 Deutsch',
                    'en' => '🇬🇧 English',
                ),
                'default' => 'current',
                'description' => __('Wählen Sie, welche Sprach-Kategorien angezeigt werden sollen.', 'wc-polylang-integration'),
            )
        );
        
        $element->add_control(
            'wc_polylang_show_flags',
            array(
                'label' => __('Sprach-Flags anzeigen', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Ja', 'wc-polylang-integration'),
                'label_off' => __('Nein', 'wc-polylang-integration'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $element->add_control(
            'wc_polylang_hierarchical_display',
            array(
                'label' => __('Hierarchische Anzeige', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Ja', 'wc-polylang-integration'),
                'label_off' => __('Nein', 'wc-polylang-integration'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Zeigt Kategorien in hierarchischer Struktur (Sprache → Kategorien).', 'wc-polylang-integration'),
            )
        );
        
        wc_polylang_elementor_debug_log("Sprach-Controls zu Widget hinzugefügt");
    }
    
    /**
     * Modifiziere Category Widget Content basierend auf Einstellungen
     */
    public function modify_category_widgets_content($data, $post_id) {
        wc_polylang_elementor_debug_log("modify_category_widgets_content() aufgerufen für Post: " . $post_id);
        
        if (!is_array($data)) {
            return $data;
        }
        
        try {
            $data = $this->process_elementor_data($data);
            wc_polylang_elementor_debug_log("Widget-Content erfolgreich modifiziert");
        } catch (Exception $e) {
            wc_polylang_elementor_debug_log("Fehler beim Modifizieren des Widget-Contents: " . $e->getMessage(), 'ERROR');
        }
        
        return $data;
    }
    
    /**
     * Verarbeite Elementor-Daten rekursiv
     */
    private function process_elementor_data($data) {
        foreach ($data as &$element) {
            if (isset($element['widgetType']) && in_array($element['widgetType'], array('woocommerce-categories', 'woocommerce-product-categories'))) {
                $element = $this->modify_category_widget($element);
            }
            
            if (isset($element['elements']) && is_array($element['elements'])) {
                $element['elements'] = $this->process_elementor_data($element['elements']);
            }
        }
        
        return $data;
    }
    
    /**
     * Modifiziere einzelnes Category Widget
     */
    private function modify_category_widget($element) {
        wc_polylang_elementor_debug_log("modify_category_widget() aufgerufen");
        
        $settings = isset($element['settings']) ? $element['settings'] : array();
        
        // Sprach-Filter anwenden
        $language_filter = isset($settings['wc_polylang_language_filter']) ? $settings['wc_polylang_language_filter'] : 'current';
        $show_flags = isset($settings['wc_polylang_show_flags']) ? $settings['wc_polylang_show_flags'] : 'yes';
        $hierarchical = isset($settings['wc_polylang_hierarchical_display']) ? $settings['wc_polylang_hierarchical_display'] : 'yes';
        
        // Modifiziere Widget-Ausgabe basierend auf Einstellungen
        if ($hierarchical === 'yes') {
            $element['settings']['hierarchical'] = 'yes';
            $element['settings']['show_count'] = 'yes';
        }
        
        // Füge Custom CSS-Klassen hinzu
        $css_classes = isset($element['settings']['_css_classes']) ? $element['settings']['_css_classes'] : '';
        $css_classes .= ' wc-polylang-categories';
        
        if ($show_flags === 'yes') {
            $css_classes .= ' show-language-flags';
        }
        
        if ($hierarchical === 'yes') {
            $css_classes .= ' hierarchical-display';
        }
        
        $element['settings']['_css_classes'] = trim($css_classes);
        
        wc_polylang_elementor_debug_log("Category Widget modifiziert - Filter: {$language_filter}, Flags: {$show_flags}, Hierarchical: {$hierarchical}");
        
        return $element;
    }
    
    /**
     * Template-Duplikation für verschiedene Sprachen
     */
    public function duplicate_template_for_languages($template_id) {
        wc_polylang_elementor_debug_log("duplicate_template_for_languages() aufgerufen für Template: " . $template_id);
        
        if (!function_exists('pll_languages_list')) {
            wc_polylang_elementor_debug_log("Polylang nicht verfügbar für Template-Duplikation", 'WARNING');
            return;
        }
        
        try {
            $template = get_post($template_id);
            if (!$template || $template->post_type !== 'elementor_library') {
                return;
            }
            
            $languages = pll_languages_list();
            $current_lang = function_exists('pll_get_post_language') ? pll_get_post_language($template_id) : 'de';
            
            foreach ($languages as $lang) {
                if ($lang === $current_lang) {
                    continue; // Skip current language
                }
                
                wc_polylang_elementor_debug_log("Dupliziere Template für Sprache: " . $lang);
                
                // Erstelle Template-Kopie
                $new_template_id = $this->create_template_copy($template, $lang);
                
                if ($new_template_id) {
                    // Verknüpfe Templates als Übersetzungen
                    if (function_exists('pll_save_post_translations')) {
                        $translations = function_exists('pll_get_post_translations') ? pll_get_post_translations($template_id) : array();
                        $translations[$lang] = $new_template_id;
                        pll_save_post_translations($translations);
                        
                        wc_polylang_elementor_debug_log("Template-Übersetzung verknüpft: " . json_encode($translations));
                    }
                }
            }
            
        } catch (Exception $e) {
            wc_polylang_elementor_debug_log("Fehler bei Template-Duplikation: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Erstelle Template-Kopie für Sprache
     */
    private function create_template_copy($original_template, $target_lang) {
        wc_polylang_elementor_debug_log("create_template_copy() für Sprache: " . $target_lang);
        
        try {
            $lang_suffix = strtoupper($target_lang);
            
            $new_template = array(
                'post_title' => $original_template->post_title . ' (' . $lang_suffix . ')',
                'post_content' => $original_template->post_content,
                'post_status' => $original_template->post_status,
                'post_type' => $original_template->post_type,
                'post_author' => $original_template->post_author,
            );
            
            $new_template_id = wp_insert_post($new_template);
            
            if (is_wp_error($new_template_id)) {
                wc_polylang_elementor_debug_log("Fehler beim Erstellen der Template-Kopie: " . $new_template_id->get_error_message(), 'ERROR');
                return false;
            }
            
            // Kopiere Meta-Daten
            $meta_keys = get_post_meta($original_template->ID);
            foreach ($meta_keys as $key => $values) {
                foreach ($values as $value) {
                    add_post_meta($new_template_id, $key, maybe_unserialize($value));
                }
            }
            
            // Setze Sprache für neues Template
            if (function_exists('pll_set_post_language')) {
                pll_set_post_language($new_template_id, $target_lang);
            }
            
            wc_polylang_elementor_debug_log("Template-Kopie erfolgreich erstellt: " . $new_template_id);
            return $new_template_id;
            
        } catch (Exception $e) {
            wc_polylang_elementor_debug_log("Fehler beim Erstellen der Template-Kopie: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * AJAX: Template-Duplikation
     */
    public function ajax_duplicate_template() {
        wc_polylang_elementor_debug_log("ajax_duplicate_template() aufgerufen");
        
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_elementor')) {
            wp_die('Nonce verification failed');
        }
        
        $template_id = intval($_POST['template_id']);
        $target_lang = sanitize_text_field($_POST['target_lang']);
        
        try {
            $this->duplicate_template_for_languages($template_id);
            wp_send_json_success('Template erfolgreich dupliziert!');
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Duplizieren: ' . $e->getMessage());
        }
    }
}

wc_polylang_elementor_debug_log("class-wc-polylang-elementor.php erfolgreich geladen");
