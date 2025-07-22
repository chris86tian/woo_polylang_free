<?php
/**
 * Custom fields translation support
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Custom_Fields {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        if (wc_polylang_get_settings()['enable_custom_fields_translation'] !== 'yes') {
            return;
        }
        
        try {
            // ACF integration
            if (class_exists('ACF')) {
                $this->init_acf_integration();
            }
            
            // RankMath integration
            if (class_exists('RankMath')) {
                $this->init_rankmath_integration();
            }
            
            // Custom meta fields
            add_action('save_post', array($this, 'handle_custom_meta_fields'), 10, 2);
            add_filter('get_post_metadata', array($this, 'translate_custom_meta_fields'), 10, 4);
            
            // Product custom fields
            add_action('woocommerce_product_options_general_product_data', array($this, 'add_translation_fields'));
            add_action('woocommerce_process_product_meta', array($this, 'save_translation_fields'));
            
            // Category custom fields
            add_action('product_cat_add_form_fields', array($this, 'add_category_translation_fields'));
            add_action('product_cat_edit_form_fields', array($this, 'edit_category_translation_fields'));
            add_action('edited_product_cat', array($this, 'save_category_translation_fields'));
            add_action('create_product_cat', array($this, 'save_category_translation_fields'));
            
            wc_polylang_debug_log('Custom fields translation initialized');
            
        } catch (Exception $e) {
            wc_polylang_debug_log('Error in custom fields init: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize ACF integration
     */
    private function init_acf_integration() {
        // Make ACF fields translatable
        add_filter('acf/settings/current_language', array($this, 'acf_current_language'));
        add_filter('acf/settings/default_language', array($this, 'acf_default_language'));
        
        // Handle ACF field groups
        add_filter('acf/location/rule_values/post_type', array($this, 'acf_location_rules'));
        
        // Translate ACF field values
        add_filter('acf/load_value', array($this, 'translate_acf_value'), 10, 3);
        add_filter('acf/update_value', array($this, 'save_acf_translation'), 10, 3);
        
        // Handle ACF options pages
        if (function_exists('acf_add_options_page')) {
            add_action('acf/init', array($this, 'register_acf_options_translations'));
        }
        
        wc_polylang_debug_log('ACF integration initialized');
    }
    
    /**
     * Get ACF current language
     */
    public function acf_current_language($language) {
        if (function_exists('pll_current_language')) {
            return pll_current_language() ?: $language;
        }
        return $language;
    }
    
    /**
     * Get ACF default language
     */
    public function acf_default_language($language) {
        if (function_exists('pll_default_language')) {
            return pll_default_language() ?: $language;
        }
        return $language;
    }
    
    /**
     * Handle ACF location rules
     */
    public function acf_location_rules($choices) {
        // Add WooCommerce post types to ACF location rules
        $choices['product'] = __('Product', 'woocommerce');
        $choices['shop_order'] = __('Order', 'woocommerce');
        $choices['shop_coupon'] = __('Coupon', 'woocommerce');
        
        return $choices;
    }
    
    /**
     * Translate ACF field values
     */
    public function translate_acf_value($value, $post_id, $field) {
        if (!function_exists('pll_current_language') || !$post_id) {
            return $value;
        }
        
        $current_language = pll_current_language();
        $post_language = function_exists('pll_get_post_language') ? pll_get_post_language($post_id) : null;
        
        // If post is in different language, try to get translated value
        if ($post_language && $post_language !== $current_language) {
            if (function_exists('pll_get_post')) {
                $translated_post_id = pll_get_post($post_id, $current_language);
                if ($translated_post_id) {
                    $translated_value = get_field($field['name'], $translated_post_id, false);
                    if ($translated_value !== null) {
                        return $translated_value;
                    }
                }
            }
        }
        
        return $value;
    }
    
    /**
     * Save ACF translation
     */
    public function save_acf_translation($value, $post_id, $field) {
        // Register translatable strings for text fields
        if (in_array($field['type'], array('text', 'textarea', 'wysiwyg')) && function_exists('pll_register_string')) {
            $string_name = 'ACF Field: ' . $field['label'] . ' (Post ' . $post_id . ')';
            pll_register_string($string_name, $value, 'ACF Fields');
        }
        
        return $value;
    }
    
    /**
     * Register ACF options translations
     */
    public function register_acf_options_translations() {
        // Get all ACF option fields
        $field_groups = acf_get_field_groups(array('options_page' => 'acf-options'));
        
        foreach ($field_groups as $field_group) {
            $fields = acf_get_fields($field_group);
            
            foreach ($fields as $field) {
                if (in_array($field['type'], array('text', 'textarea', 'wysiwyg'))) {
                    $value = get_field($field['name'], 'option');
                    
                    if ($value && function_exists('pll_register_string')) {
                        $string_name = 'ACF Option: ' . $field['label'];
                        pll_register_string($string_name, $value, 'ACF Options');
                    }
                }
            }
        }
    }
    
    /**
     * Initialize RankMath integration
     */
    private function init_rankmath_integration() {
        // Handle RankMath meta fields
        add_filter('rank_math/frontend/title', array($this, 'translate_rankmath_meta'), 10, 1);
        add_filter('rank_math/frontend/description', array($this, 'translate_rankmath_meta'), 10, 1);
        
        // Register RankMath strings for translation
        add_action('save_post', array($this, 'register_rankmath_strings'), 20, 2);
        
        wc_polylang_debug_log('RankMath integration initialized');
    }
    
    /**
     * Translate RankMath meta
     */
    public function translate_rankmath_meta($value) {
        if (!function_exists('pll__') || empty($value)) {
            return $value;
        }
        
        $translated_value = pll__($value);
        return $translated_value ?: $value;
    }
    
    /**
     * Register RankMath strings
     */
    public function register_rankmath_strings($post_id, $post) {
        if (!function_exists('pll_register_string') || $post->post_type !== 'product') {
            return;
        }
        
        // Register RankMath SEO fields
        $rankmath_fields = array(
            'rank_math_title' => 'RankMath Title',
            'rank_math_description' => 'RankMath Description',
            'rank_math_focus_keyword' => 'RankMath Focus Keyword'
        );
        
        foreach ($rankmath_fields as $meta_key => $label) {
            $value = get_post_meta($post_id, $meta_key, true);
            if ($value) {
                $string_name = $label . ' (Post ' . $post_id . ')';
                pll_register_string($string_name, $value, 'RankMath SEO');
            }
        }
    }
    
    /**
     * Handle custom meta fields
     */
    public function handle_custom_meta_fields($post_id, $post) {
        if (!function_exists('pll_register_string') || $post->post_type !== 'product') {
            return;
        }
        
        // Get all custom fields for this post
        $custom_fields = get_post_meta($post_id);
        
        // Define translatable field patterns
        $translatable_patterns = array(
            '_product_subtitle',
            '_product_banner_text',
            '_product_features',
            '_product_specifications',
            '_custom_description',
            '_additional_info'
        );
        
        foreach ($custom_fields as $meta_key => $meta_values) {
            // Check if field should be translatable
            $is_translatable = false;
            foreach ($translatable_patterns as $pattern) {
                if (strpos($meta_key, $pattern) !== false) {
                    $is_translatable = true;
                    break;
                }
            }
            
            if ($is_translatable && !empty($meta_values[0])) {
                $string_name = 'Custom Field: ' . $meta_key . ' (Post ' . $post_id . ')';
                pll_register_string($string_name, $meta_values[0], 'Custom Fields');
            }
        }
    }
    
    /**
     * Translate custom meta fields
     */
    public function translate_custom_meta_fields($value, $object_id, $meta_key, $single) {
        if (!function_exists('pll__') || !$object_id) {
            return $value;
        }
        
        // Only translate specific fields
        $translatable_fields = array(
            '_product_subtitle',
            '_product_banner_text',
            '_product_features',
            '_product_specifications',
            '_custom_description',
            '_additional_info'
        );
        
        if (!in_array($meta_key, $translatable_fields)) {
            return $value;
        }
        
        // Get original value
        remove_filter('get_post_metadata', array($this, 'translate_custom_meta_fields'), 10);
        $original_value = get_post_meta($object_id, $meta_key, $single);
        add_filter('get_post_metadata', array($this, 'translate_custom_meta_fields'), 10, 4);
        
        if (empty($original_value)) {
            return $value;
        }
        
        $string_name = 'Custom Field: ' . $meta_key . ' (Post ' . $object_id . ')';
        $translated_value = pll__($string_name);
        
        if ($translated_value && $translated_value !== $string_name) {
            return $single ? $translated_value : array($translated_value);
        }
        
        return $value;
    }
    
    /**
     * Add translation fields to product
     */
    public function add_translation_fields() {
        global $post;
        
        if (!function_exists('pll_languages_list')) {
            return;
        }
        
        echo '<div class="options_group">';
        echo '<h3>' . __('Übersetzungsfelder', 'wc-polylang-integration') . '</h3>';
        
        // Product subtitle
        woocommerce_wp_text_input(array(
            'id' => '_product_subtitle',
            'label' => __('Produkt-Untertitel', 'wc-polylang-integration'),
            'description' => __('Zusätzlicher Untertitel für das Produkt', 'wc-polylang-integration'),
            'desc_tip' => true
        ));
        
        // Product banner text
        woocommerce_wp_textarea_input(array(
            'id' => '_product_banner_text',
            'label' => __('Banner-Text', 'wc-polylang-integration'),
            'description' => __('Text für Produkt-Banner oder Highlights', 'wc-polylang-integration'),
            'desc_tip' => true
        ));
        
        // Additional info
        woocommerce_wp_textarea_input(array(
            'id' => '_additional_info',
            'label' => __('Zusätzliche Informationen', 'wc-polylang-integration'),
            'description' => __('Weitere übersetzbare Produktinformationen', 'wc-polylang-integration'),
            'desc_tip' => true
        ));
        
        echo '</div>';
    }
    
    /**
     * Save translation fields
     */
    public function save_translation_fields($post_id) {
        $fields = array('_product_subtitle', '_product_banner_text', '_additional_info');
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_textarea_field($_POST[$field]);
                update_post_meta($post_id, $field, $value);
                
                // Register for translation
                if (function_exists('pll_register_string') && !empty($value)) {
                    $string_name = 'Custom Field: ' . $field . ' (Post ' . $post_id . ')';
                    pll_register_string($string_name, $value, 'Custom Fields');
                }
            }
        }
    }
    
    /**
     * Add category translation fields
     */
    public function add_category_translation_fields($taxonomy) {
        ?>
        <div class="form-field">
            <label for="category_banner_text"><?php _e('Banner-Text', 'wc-polylang-integration'); ?></label>
            <textarea name="category_banner_text" id="category_banner_text" rows="3" cols="40"></textarea>
            <p class="description"><?php _e('Zusätzlicher Text für die Kategorie-Seite', 'wc-polylang-integration'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="category_seo_text"><?php _e('SEO-Text', 'wc-polylang-integration'); ?></label>
            <textarea name="category_seo_text" id="category_seo_text" rows="5" cols="40"></textarea>
            <p class="description"><?php _e('SEO-optimierter Text für die Kategorie', 'wc-polylang-integration'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Edit category translation fields
     */
    public function edit_category_translation_fields($term, $taxonomy) {
        $banner_text = get_term_meta($term->term_id, 'category_banner_text', true);
        $seo_text = get_term_meta($term->term_id, 'category_seo_text', true);
        ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="category_banner_text"><?php _e('Banner-Text', 'wc-polylang-integration'); ?></label></th>
            <td>
                <textarea name="category_banner_text" id="category_banner_text" rows="3" cols="50"><?php echo esc_textarea($banner_text); ?></textarea>
                <p class="description"><?php _e('Zusätzlicher Text für die Kategorie-Seite', 'wc-polylang-integration'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row" valign="top"><label for="category_seo_text"><?php _e('SEO-Text', 'wc-polylang-integration'); ?></label></th>
            <td>
                <textarea name="category_seo_text" id="category_seo_text" rows="5" cols="50"><?php echo esc_textarea($seo_text); ?></textarea>
                <p class="description"><?php _e('SEO-optimierter Text für die Kategorie', 'wc-polylang-integration'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save category translation fields
     */
    public function save_category_translation_fields($term_id) {
        $fields = array('category_banner_text', 'category_seo_text');
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_textarea_field($_POST[$field]);
                update_term_meta($term_id, $field, $value);
                
                // Register for translation
                if (function_exists('pll_register_string') && !empty($value)) {
                    $string_name = 'Category Field: ' . $field . ' (Term ' . $term_id . ')';
                    pll_register_string($string_name, $value, 'Category Fields');
                }
            }
        }
    }
    
    /**
     * Get translated custom field
     */
    public function get_translated_custom_field($post_id, $field_name, $language = null) {
        if (!function_exists('pll__')) {
            return get_post_meta($post_id, $field_name, true);
        }
        
        $language = $language ?: pll_current_language();
        $string_name = 'Custom Field: ' . $field_name . ' (Post ' . $post_id . ')';
        
        $translated_value = pll__($string_name);
        
        if ($translated_value && $translated_value !== $string_name) {
            return $translated_value;
        }
        
        return get_post_meta($post_id, $field_name, true);
    }
    
    /**
     * Get translated term meta
     */
    public function get_translated_term_meta($term_id, $field_name, $language = null) {
        if (!function_exists('pll__')) {
            return get_term_meta($term_id, $field_name, true);
        }
        
        $language = $language ?: pll_current_language();
        $string_name = 'Category Field: ' . $field_name . ' (Term ' . $term_id . ')';
        
        $translated_value = pll__($string_name);
        
        if ($translated_value && $translated_value !== $string_name) {
            return $translated_value;
        }
        
        return get_term_meta($term_id, $field_name, true);
    }
    
    /**
     * Sync custom fields between translations
     */
    public function sync_custom_fields($source_post_id, $target_post_id, $fields = array()) {
        if (empty($fields)) {
            // Default fields to sync
            $fields = array(
                '_product_subtitle',
                '_product_banner_text',
                '_additional_info'
            );
        }
        
        foreach ($fields as $field) {
            $value = get_post_meta($source_post_id, $field, true);
            if ($value) {
                update_post_meta($target_post_id, $field, $value);
                
                // Register for translation
                if (function_exists('pll_register_string')) {
                    $string_name = 'Custom Field: ' . $field . ' (Post ' . $target_post_id . ')';
                    pll_register_string($string_name, $value, 'Custom Fields');
                }
            }
        }
    }
    
    /**
     * Handle field group translations
     */
    public function handle_field_group_translations($field_group_id) {
        if (!function_exists('acf_get_fields') || !function_exists('pll_register_string')) {
            return;
        }
        
        $fields = acf_get_fields($field_group_id);
        
        foreach ($fields as $field) {
            // Register field labels and instructions
            if (!empty($field['label'])) {
                pll_register_string('ACF Field Label: ' . $field['name'], $field['label'], 'ACF Field Labels');
            }
            
            if (!empty($field['instructions'])) {
                pll_register_string('ACF Field Instructions: ' . $field['name'], $field['instructions'], 'ACF Field Instructions');
            }
            
            // Handle choice fields
            if (in_array($field['type'], array('select', 'checkbox', 'radio')) && !empty($field['choices'])) {
                foreach ($field['choices'] as $value => $label) {
                    pll_register_string('ACF Choice: ' . $field['name'] . ' - ' . $value, $label, 'ACF Choices');
                }
            }
        }
    }
    
    /**
     * Export translatable strings
     */
    public function export_translatable_strings($post_id = null) {
        $strings = array();
        
        if ($post_id) {
            // Export strings for specific post
            $custom_fields = get_post_meta($post_id);
            
            foreach ($custom_fields as $meta_key => $meta_values) {
                if (strpos($meta_key, '_product_') === 0 || strpos($meta_key, '_custom_') === 0) {
                    $strings[$meta_key] = $meta_values[0];
                }
            }
        } else {
            // Export all registered strings
            if (function_exists('pll_get_strings')) {
                $pll_strings = pll_get_strings();
                
                foreach ($pll_strings as $string) {
                    if (strpos($string['context'], 'Custom Fields') !== false || 
                        strpos($string['context'], 'ACF') !== false) {
                        $strings[$string['name']] = $string['string'];
                    }
                }
            }
        }
        
        return $strings;
    }
    
    /**
     * Import translatable strings
     */
    public function import_translatable_strings($strings, $language) {
        if (!function_exists('pll_register_string')) {
            return false;
        }
        
        foreach ($strings as $name => $value) {
            pll_register_string($name, $value, 'Imported Strings');
        }
        
        return true;
    }
}
