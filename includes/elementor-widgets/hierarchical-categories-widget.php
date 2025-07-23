<?php
/**
 * Custom Elementor Widget für Hierarchische Kategorien
 * Entwickelt von LipaLIFE - www.lipalife.de
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Hierarchical_Categories_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'wc-polylang-hierarchical-categories';
    }
    
    public function get_title() {
        return __('🌍 Hierarchische Kategorien', 'wc-polylang-integration');
    }
    
    public function get_icon() {
        return 'eicon-product-categories';
    }
    
    public function get_categories() {
        return array('woocommerce-elements');
    }
    
    public function get_keywords() {
        return array('woocommerce', 'categories', 'polylang', 'multilingual', 'hierarchical');
    }
    
    protected function _register_controls() {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Inhalt', 'wc-polylang-integration'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'display_mode',
            array(
                'label' => __('Anzeigemodus', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'hierarchical' => __('Hierarchisch (Sprache → Kategorien)', 'wc-polylang-integration'),
                    'flat' => __('Flach (alle Kategorien)', 'wc-polylang-integration'),
                    'language_tabs' => __('Sprach-Tabs', 'wc-polylang-integration'),
                ),
                'default' => 'hierarchical',
            )
        );
        
        $this->add_control(
            'language_filter',
            array(
                'label' => __('Sprach-Filter', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_available_languages(),
                'default' => array('de', 'en'),
            )
        );
        
        $this->add_control(
            'show_flags',
            array(
                'label' => __('Sprach-Flags anzeigen', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Ja', 'wc-polylang-integration'),
                'label_off' => __('Nein', 'wc-polylang-integration'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_count',
            array(
                'label' => __('Produktanzahl anzeigen', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Ja', 'wc-polylang-integration'),
                'label_off' => __('Nein', 'wc-polylang-integration'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_empty',
            array(
                'label' => __('Leere Kategorien anzeigen', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Ja', 'wc-polylang-integration'),
                'label_off' => __('Nein', 'wc-polylang-integration'),
                'return_value' => 'yes',
                'default' => 'no',
            )
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            array(
                'label' => __('Stil', 'wc-polylang-integration'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'layout_style',
            array(
                'label' => __('Layout-Stil', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'list' => __('Liste', 'wc-polylang-integration'),
                    'grid' => __('Grid', 'wc-polylang-integration'),
                    'accordion' => __('Akkordeon', 'wc-polylang-integration'),
                ),
                'default' => 'list',
            )
        );
        
        $this->add_responsive_control(
            'columns',
            array(
                'label' => __('Spalten', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ),
                'default' => '2',
                'condition' => array(
                    'layout_style' => 'grid',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'category_typography',
                'label' => __('Kategorie-Typografie', 'wc-polylang-integration'),
                'selector' => '{{WRAPPER}} .wc-polylang-category-item',
            )
        );
        
        $this->add_control(
            'category_color',
            array(
                'label' => __('Kategorie-Farbe', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wc-polylang-category-item a' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'category_hover_color',
            array(
                'label' => __('Kategorie-Hover-Farbe', 'wc-polylang-integration'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wc-polylang-category-item a:hover' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $display_mode = $settings['display_mode'];
        $language_filter = $settings['language_filter'];
        $show_flags = $settings['show_flags'] === 'yes';
        $show_count = $settings['show_count'] === 'yes';
        $show_empty = $settings['show_empty'] === 'yes';
        $layout_style = $settings['layout_style'];
        $columns = $settings['columns'];
        
        // Hole hierarchische Struktur
        $structure = get_option('wc_polylang_hierarchical_structure', array());
        
        if (empty($structure)) {
            echo '<div class="wc-polylang-notice">';
            echo '<p>' . __('Noch keine hierarchische Kategorie-Struktur erstellt.', 'wc-polylang-integration') . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=wc-polylang-categories') . '">' . __('Jetzt erstellen', 'wc-polylang-integration') . '</a></p>';
            echo '</div>';
            return;
        }
        
        $css_classes = array(
            'wc-polylang-hierarchical-categories',
            'layout-' . $layout_style
        );
        
        if ($layout_style === 'grid') {
            $css_classes[] = 'columns-' . $columns;
        }
        
        echo '<div class="' . implode(' ', $css_classes) . '">';
        
        switch ($display_mode) {
            case 'hierarchical':
                $this->render_hierarchical($structure, $language_filter, $show_flags, $show_count, $show_empty);
                break;
            case 'flat':
                $this->render_flat($structure, $language_filter, $show_flags, $show_count, $show_empty);
                break;
            case 'language_tabs':
                $this->render_language_tabs($structure, $language_filter, $show_flags, $show_count, $show_empty);
                break;
        }
        
        echo '</div>';
        
        // CSS für Widget
        $this->render_widget_css($settings);
    }
    
    private function render_hierarchical($structure, $language_filter, $show_flags, $show_count, $show_empty) {
        echo '<ul class="hierarchical-category-list">';
        
        foreach ($structure as $lang => $data) {
            if (!empty($language_filter) && !in_array($lang, $language_filter)) {
                continue;
            }
            
            $flag = $this->get_language_flag($lang);
            $lang_name = $this->get_language_name($lang);
            
            if (isset($data['parent'])) {
                $parent_term = get_term($data['parent'], 'product_cat');
                if ($parent_term && !is_wp_error($parent_term)) {
                    echo '<li class="language-parent">';
                    echo '<span class="language-header">';
                    if ($show_flags) echo '<span class="lang-flag">' . $flag . '</span> ';
                    echo '<strong>' . esc_html($lang_name) . '</strong>';
                    if ($show_count) echo ' <span class="count">(' . $this->get_language_total_count($data) . ')</span>';
                    echo '</span>';
                    
                    if (isset($data['children']) && is_array($data['children'])) {
                        echo '<ul class="category-children">';
                        foreach ($data['children'] as $child_id) {
                            $child_term = get_term($child_id, 'product_cat');
                            if ($child_term && !is_wp_error($child_term)) {
                                if (!$show_empty && $child_term->count == 0) continue;
                                
                                echo '<li class="wc-polylang-category-item">';
                                echo '<a href="' . get_term_link($child_term) . '">';
                                echo esc_html($child_term->name);
                                if ($show_count) echo ' <span class="count">(' . $child_term->count . ')</span>';
                                echo '</a>';
                                echo '</li>';
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
    
    private function render_flat($structure, $language_filter, $show_flags, $show_count, $show_empty) {
        echo '<ul class="flat-category-list">';
        
        foreach ($structure as $lang => $data) {
            if (!empty($language_filter) && !in_array($lang, $language_filter)) {
                continue;
            }
            
            $flag = $this->get_language_flag($lang);
            
            if (isset($data['children']) && is_array($data['children'])) {
                foreach ($data['children'] as $child_id) {
                    $child_term = get_term($child_id, 'product_cat');
                    if ($child_term && !is_wp_error($child_term)) {
                        if (!$show_empty && $child_term->count == 0) continue;
                        
                        echo '<li class="wc-polylang-category-item">';
                        echo '<a href="' . get_term_link($child_term) . '">';
                        if ($show_flags) echo '<span class="lang-flag">' . $flag . '</span> ';
                        echo esc_html($child_term->name);
                        if ($show_count) echo ' <span class="count">(' . $child_term->count . ')</span>';
                        echo '</a>';
                        echo '</li>';
                    }
                }
            }
        }
        
        echo '</ul>';
    }
    
    private function render_language_tabs($structure, $language_filter, $show_flags, $show_count, $show_empty) {
        echo '<div class="language-tabs-container">';
        
        // Tab-Navigation
        echo '<ul class="language-tabs-nav">';
        foreach ($structure as $lang => $data) {
            if (!empty($language_filter) && !in_array($lang, $language_filter)) {
                continue;
            }
            
            $flag = $this->get_language_flag($lang);
            $lang_name = $this->get_language_name($lang);
            $active = $lang === array_keys($structure)[0] ? ' active' : '';
            
            echo '<li class="tab-nav-item' . $active . '" data-lang="' . $lang . '">';
            if ($show_flags) echo '<span class="lang-flag">' . $flag . '</span> ';
            echo esc_html($lang_name);
            echo '</li>';
        }
        echo '</ul>';
        
        // Tab-Content
        echo '<div class="language-tabs-content">';
        foreach ($structure as $lang => $data) {
            if (!empty($language_filter) && !in_array($lang, $language_filter)) {
                continue;
            }
            
            $active = $lang === array_keys($structure)[0] ? ' active' : '';
            
            echo '<div class="tab-content' . $active . '" data-lang="' . $lang . '">';
            if (isset($data['children']) && is_array($data['children'])) {
                echo '<ul class="tab-category-list">';
                foreach ($data['children'] as $child_id) {
                    $child_term = get_term($child_id, 'product_cat');
                    if ($child_term && !is_wp_error($child_term)) {
                        if (!$show_empty && $child_term->count == 0) continue;
                        
                        echo '<li class="wc-polylang-category-item">';
                        echo '<a href="' . get_term_link($child_term) . '">';
                        echo esc_html($child_term->name);
                        if ($show_count) echo ' <span class="count">(' . $child_term->count . ')</span>';
                        echo '</a>';
                        echo '</li>';
                    }
                }
                echo '</ul>';
            }
            echo '</div>';
        }
        echo '</div>';
        
        echo '</div>';
        
        // JavaScript für Tabs
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const tabNavs = document.querySelectorAll(".language-tabs-nav .tab-nav-item");
            const tabContents = document.querySelectorAll(".language-tabs-content .tab-content");
            
            tabNavs.forEach(function(nav) {
                nav.addEventListener("click", function() {
                    const lang = this.getAttribute("data-lang");
                    
                    // Remove active class from all
                    tabNavs.forEach(n => n.classList.remove("active"));
                    tabContents.forEach(c => c.classList.remove("active"));
                    
                    // Add active class to clicked
                    this.classList.add("active");
                    document.querySelector(".tab-content[data-lang=\"" + lang + "\"]").classList.add("active");
                });
            });
        });
        </script>';
    }
    
    private function render_widget_css($settings) {
        $layout_style = $settings['layout_style'];
        $columns = $settings['columns'];
        
        echo '<style>
        .wc-polylang-hierarchical-categories {
            margin: 0;
            padding: 0;
        }
        
        .hierarchical-category-list,
        .flat-category-list,
        .tab-category-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .language-parent {
            margin-bottom: 20px;
        }
        
        .language-header {
            display: block;
            font-size: 1.2em;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #eee;
        }
        
        .category-children {
            margin-left: 20px;
        }
        
        .wc-polylang-category-item {
            margin-bottom: 8px;
        }
        
        .wc-polylang-category-item a {
            text-decoration: none;
            display: block;
            padding: 5px 0;
        }
        
        .lang-flag {
            margin-right: 5px;
        }
        
        .count {
            color: #666;
            font-size: 0.9em;
        }
        
        /* Grid Layout */
        .layout-grid .flat-category-list {
            display: grid;
            grid-template-columns: repeat(' . $columns . ', 1fr);
            gap: 15px;
        }
        
        /* Language Tabs */
        .language-tabs-nav {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab-nav-item {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab-nav-item:hover,
        .tab-nav-item.active {
            border-bottom-color: #0073aa;
            background-color: #f9f9f9;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Accordion Layout */
        .layout-accordion .language-parent {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .layout-accordion .language-header {
            padding: 15px;
            cursor: pointer;
            background-color: #f9f9f9;
            margin: 0;
            border-bottom: none;
        }
        
        .layout-accordion .category-children {
            padding: 15px;
            margin: 0;
            border-top: 1px solid #eee;
        }
        </style>';
    }
    
    private function get_available_languages() {
        if (function_exists('pll_languages_list')) {
            $languages = pll_languages_list();
            $options = array();
            foreach ($languages as $lang) {
                $flag = $this->get_language_flag($lang);
                $name = $this->get_language_name($lang);
                $options[$lang] = $flag . ' ' . $name;
            }
            return $options;
        }
        
        return array(
            'de' => '🇩🇪 Deutsch',
            'en' => '🇬🇧 English'
        );
    }
    
    private function get_language_flag($lang) {
        $flags = array(
            'de' => '🇩🇪',
            'en' => '🇬🇧',
            'fr' => '🇫🇷',
            'es' => '🇪🇸',
            'it' => '🇮🇹',
        );
        
        return isset($flags[$lang]) ? $flags[$lang] : '🌍';
    }
    
    private function get_language_name($lang) {
        $names = array(
            'de' => 'Deutsch',
            'en' => 'English',
            'fr' => 'Français',
            'es' => 'Español',
            'it' => 'Italiano',
        );
        
        return isset($names[$lang]) ? $names[$lang] : ucfirst($lang);
    }
    
    private function get_language_total_count($data) {
        $total = 0;
        if (isset($data['children']) && is_array($data['children'])) {
            foreach ($data['children'] as $child_id) {
                $child_term = get_term($child_id, 'product_cat');
                if ($child_term && !is_wp_error($child_term)) {
                    $total += $child_term->count;
                }
            }
        }
        return $total;
    }
}
