<?php
/**
 * Elementor Pro Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Elementor {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Check if Elementor Pro is active
        if (!$this->is_elementor_pro_active()) {
            return;
        }
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_elementor_menu'));
        add_action('wp_ajax_wc_polylang_duplicate_elementor_templates', array($this, 'duplicate_templates'));
        add_action('wp_ajax_wc_polylang_sync_elementor_content', array($this, 'sync_content'));
        
        // Elementor hooks
        add_action('elementor/template/after_save', array($this, 'handle_template_save'), 10, 2);
        add_filter('elementor/theme/get_location_templates', array($this, 'filter_location_templates'), 10, 2);
        
        wc_polylang_debug_log('Elementor Pro integration initialized');
    }
    
    /**
     * Check if Elementor Pro is active
     */
    private function is_elementor_pro_active() {
        return defined('ELEMENTOR_PRO_VERSION') && class_exists('ElementorPro\Plugin');
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Add Polylang support for Elementor templates
        add_filter('pll_get_post_types', array($this, 'add_elementor_post_types'));
        
        // Handle WooCommerce template conditions
        add_filter('elementor_pro/theme_builder/conditions_manager/get_conditions', array($this, 'add_language_conditions'));
        
        // Modify Elementor widgets for multilingual support
        add_action('elementor/widgets/widgets_registered', array($this, 'modify_woocommerce_widgets'));
    }
    
    /**
     * Add Elementor post types to Polylang
     */
    public function add_elementor_post_types($post_types) {
        $post_types['elementor_library'] = 'elementor_library';
        return $post_types;
    }
    
    /**
     * Add language conditions to Elementor Pro
     */
    public function add_language_conditions($conditions) {
        if (!function_exists('pll_languages_list')) {
            return $conditions;
        }
        
        $languages = pll_languages_list();
        
        foreach ($languages as $language) {
            $conditions['general'][] = array(
                'name' => 'polylang_language_' . $language,
                'label' => sprintf(__('Language: %s', 'wc-polylang-integration'), strtoupper($language)),
                'callback' => function() use ($language) {
                    return pll_current_language() === $language;
                }
            );
        }
        
        return $conditions;
    }
    
    /**
     * Add Elementor admin menu
     */
    public function add_elementor_menu() {
        add_submenu_page(
            'wc-polylang-integration',
            __('Elementor Templates', 'wc-polylang-integration'),
            __('🎨 Elementor Templates', 'wc-polylang-integration'),
            'manage_woocommerce',
            'wc-polylang-elementor',
            array($this, 'elementor_page')
        );
    }
    
    /**
     * Elementor templates page
     */
    public function elementor_page() {
        $templates = $this->get_woocommerce_templates();
        $languages = function_exists('pll_languages_list') ? pll_languages_list() : array();
        
        ?>
        <div class="wrap">
            <h1><?php _e('🎨 Elementor Pro Template Manager', 'wc-polylang-integration'); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('Verwalten Sie Ihre mehrsprachigen Elementor Pro Templates für WooCommerce automatisch.', 'wc-polylang-integration'); ?></p>
            </div>
            
            <!-- Quick Actions -->
            <div class="wc-polylang-elementor-actions">
                <h2><?php _e('🚀 Schnellaktionen', 'wc-polylang-integration'); ?></h2>
                <div class="action-buttons">
                    <button type="button" id="duplicate-all-templates" class="button button-primary button-large">
                        <?php _e('🔄 Alle Templates duplizieren', 'wc-polylang-integration'); ?>
                    </button>
                    <button type="button" id="sync-template-content" class="button button-secondary button-large">
                        <?php _e('🔗 Inhalte synchronisieren', 'wc-polylang-integration'); ?>
                    </button>
                    <button type="button" id="setup-conditions" class="button button-secondary button-large">
                        <?php _e('⚙️ Bedingungen einrichten', 'wc-polylang-integration'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Template Status -->
            <div class="wc-polylang-template-status">
                <h2><?php _e('Template Status', 'wc-polylang-integration'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Template', 'wc-polylang-integration'); ?></th>
                            <th><?php _e('Typ', 'wc-polylang-integration'); ?></th>
                            <th><?php _e('Deutsch', 'wc-polylang-integration'); ?></th>
                            <th><?php _e('Englisch', 'wc-polylang-integration'); ?></th>
                            <th><?php _e('Status', 'wc-polylang-integration'); ?></th>
                            <th><?php _e('Aktionen', 'wc-polylang-integration'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><strong><?php echo esc_html($template['title']); ?></strong></td>
                            <td>
                                <span class="template-type <?php echo esc_attr($template['type']); ?>">
                                    <?php echo esc_html($this->get_template_type_label($template['type'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($template['de']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                    <a href="<?php echo esc_url($template['de']['edit_url']); ?>" target="_blank">
                                        <?php _e('Bearbeiten', 'wc-polylang-integration'); ?>
                                    </a>
                                    <br><small><?php echo esc_html($template['de']['conditions']); ?></small>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no-alt" style="color: red;"></span>
                                    <?php _e('Nicht gefunden', 'wc-polylang-integration'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($template['en']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                    <a href="<?php echo esc_url($template['en']['edit_url']); ?>" target="_blank">
                                        <?php _e('Bearbeiten', 'wc-polylang-integration'); ?>
                                    </a>
                                    <br><small><?php echo esc_html($template['en']['conditions']); ?></small>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no-alt" style="color: red;"></span>
                                    <?php _e('Nicht erstellt', 'wc-polylang-integration'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($template['de'] && $template['en']): ?>
                                    <span class="status-complete"><?php _e('✓ Vollständig', 'wc-polylang-integration'); ?></span>
                                <?php elseif ($template['de']): ?>
                                    <span class="status-ready"><?php _e('⚡ Bereit zum Duplizieren', 'wc-polylang-integration'); ?></span>
                                <?php else: ?>
                                    <span class="status-missing"><?php _e('❌ Template fehlt', 'wc-polylang-integration'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($template['de'] && !$template['en']): ?>
                                    <button type="button" class="button button-small duplicate-single" 
                                            data-template-id="<?php echo esc_attr($template['de']['id']); ?>">
                                        <?php _e('Duplizieren', 'wc-polylang-integration'); ?>
                                    </button>
                                <?php elseif ($template['de'] && $template['en']): ?>
                                    <button type="button" class="button button-small sync-single" 
                                            data-de-id="<?php echo esc_attr($template['de']['id']); ?>"
                                            data-en-id="<?php echo esc_attr($template['en']['id']); ?>">
                                        <?php _e('Sync', 'wc-polylang-integration'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Template Guide -->
            <div class="wc-polylang-template-guide">
                <h2><?php _e('📖 Template-Anleitung', 'wc-polylang-integration'); ?></h2>
                <div class="guide-content">
                    <h3><?php _e('Automatische Template-Duplikation:', 'wc-polylang-integration'); ?></h3>
                    <ol>
                        <li><?php _e('Klicken Sie auf <strong>"Alle Templates duplizieren"</strong>', 'wc-polylang-integration'); ?></li>
                        <li><?php _e('Das Plugin erstellt automatisch <strong>englische Versionen</strong> Ihrer deutschen Templates', 'wc-polylang-integration'); ?></li>
                        <li><?php _e('<strong>Sprachbedingungen</strong> werden automatisch gesetzt', 'wc-polylang-integration'); ?></li>
                        <li><?php _e('Templates sind sofort <strong>einsatzbereit</strong>', 'wc-polylang-integration'); ?></li>
                    </ol>
                    
                    <h3><?php _e('Manuelle Anpassungen:', 'wc-polylang-integration'); ?></h3>
                    <ul>
                        <li><?php _e('Bearbeiten Sie die <strong>englischen Templates</strong> über die "Bearbeiten"-Links', 'wc-polylang-integration'); ?></li>
                        <li><?php _e('Passen Sie <strong>Texte und Inhalte</strong> an die englische Sprache an', 'wc-polylang-integration'); ?></li>
                        <li><?php _e('Die <strong>Template-Struktur</strong> bleibt erhalten', 'wc-polylang-integration'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div id="template-progress" style="display: none; margin-top: 20px;">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <p id="progress-text"><?php _e('Templates werden verarbeitet...', 'wc-polylang-integration'); ?></p>
            </div>
            
            <div id="template-results" style="display: none; margin-top: 20px;"></div>
        </div>
        
        <style>
        .wc-polylang-elementor-actions {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .wc-polylang-elementor-actions h2 {
            color: white;
            margin-top: 0;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .action-buttons .button {
            background: white;
            color: #667eea;
            border: none;
            font-weight: bold;
        }
        .action-buttons .button:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .wc-polylang-template-status {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
        }
        .template-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .template-type.archive {
            background: #e3f2fd;
            color: #1976d2;
        }
        .template-type.single {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .template-type.page {
            background: #e8f5e8;
            color: #388e3c;
        }
        .status-complete {
            color: #46b450;
            font-weight: bold;
        }
        .status-ready {
            color: #ff9800;
            font-weight: bold;
        }
        .status-missing {
            color: #dc3232;
            font-weight: bold;
        }
        .wc-polylang-template-guide {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
        }
        .guide-content ol, .guide-content ul {
            padding-left: 20px;
        }
        .guide-content li {
            margin-bottom: 8px;
            line-height: 1.5;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f1f1f1;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa, #00a0d2);
            width: 0%;
            transition: width 0.3s ease;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Duplicate all templates
            $('#duplicate-all-templates').on('click', function() {
                performTemplateAction('duplicate_all', 'Alle Templates werden dupliziert...');
            });
            
            // Sync content
            $('#sync-template-content').on('click', function() {
                performTemplateAction('sync_content', 'Template-Inhalte werden synchronisiert...');
            });
            
            // Setup conditions
            $('#setup-conditions').on('click', function() {
                performTemplateAction('setup_conditions', 'Sprachbedingungen werden eingerichtet...');
            });
            
            // Single template actions
            $('.duplicate-single').on('click', function() {
                var templateId = $(this).data('template-id');
                performTemplateAction('duplicate_single', 'Template wird dupliziert...', {template_id: templateId});
            });
            
            $('.sync-single').on('click', function() {
                var deId = $(this).data('de-id');
                var enId = $(this).data('en-id');
                performTemplateAction('sync_single', 'Templates werden synchronisiert...', {de_id: deId, en_id: enId});
            });
            
            function performTemplateAction(action, progressText, extraData = {}) {
                var progress = $('#template-progress');
                var results = $('#template-results');
                var progressFill = $('.progress-fill');
                var progressTextEl = $('#progress-text');
                
                // Disable all buttons
                $('.button').prop('disabled', true);
                progress.show();
                results.hide();
                progressTextEl.text(progressText);
                
                var data = {
                    action: 'wc_polylang_duplicate_elementor_templates',
                    template_action: action,
                    nonce: '<?php echo wp_create_nonce('wc_polylang_elementor_templates'); ?>',
                    ...extraData
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        progressFill.css('width', '100%');
                        progressTextEl.text('Abgeschlossen!');
                        
                        setTimeout(function() {
                            progress.hide();
                            results.html(response.data.message).show();
                            $('.button').prop('disabled', false);
                            
                            // Reload page after 3 seconds
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        }, 1000);
                    },
                    error: function() {
                        progress.hide();
                        results.html('<div class="notice notice-error"><p>Fehler bei der Verarbeitung. Bitte versuchen Sie es erneut.</p></div>').show();
                        $('.button').prop('disabled', false);
                    }
                });
                
                // Simulate progress
                var width = 0;
                var interval = setInterval(function() {
                    width += Math.random() * 20;
                    if (width > 90) width = 90;
                    progressFill.css('width', width + '%');
                }, 300);
                
                setTimeout(function() {
                    clearInterval(interval);
                }, 4000);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Get WooCommerce templates
     */
    private function get_woocommerce_templates() {
        $templates = array();
        
        // Get all Elementor templates
        $elementor_templates = get_posts(array(
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_elementor_template_type',
                    'value' => array('archive', 'single-product', 'product-archive', 'page'),
                    'compare' => 'IN'
                )
            )
        ));
        
        foreach ($elementor_templates as $template) {
            $template_type = get_post_meta($template->ID, '_elementor_template_type', true);
            $conditions = $this->get_template_conditions($template->ID);
            
            // Check if it's a WooCommerce related template
            if ($this->is_woocommerce_template($template, $conditions)) {
                $language = function_exists('pll_get_post_language') ? pll_get_post_language($template->ID) : 'de';
                
                $template_key = $this->get_template_key($template, $conditions);
                
                if (!isset($templates[$template_key])) {
                    $templates[$template_key] = array(
                        'title' => $template->post_title,
                        'type' => $template_type,
                        'de' => null,
                        'en' => null
                    );
                }
                
                $templates[$template_key][$language] = array(
                    'id' => $template->ID,
                    'title' => $template->post_title,
                    'edit_url' => admin_url('post.php?post=' . $template->ID . '&action=elementor'),
                    'conditions' => $this->format_conditions($conditions)
                );
            }
        }
        
        return $templates;
    }
    
    /**
     * Get template conditions
     */
    private function get_template_conditions($template_id) {
        $conditions = get_post_meta($template_id, '_elementor_conditions', true);
        return is_array($conditions) ? $conditions : array();
    }
    
    /**
     * Check if template is WooCommerce related
     */
    private function is_woocommerce_template($template, $conditions) {
        $template_type = get_post_meta($template->ID, '_elementor_template_type', true);
        
        // Check template type
        if (in_array($template_type, array('product-archive', 'single-product'))) {
            return true;
        }
        
        // Check conditions
        foreach ($conditions as $condition) {
            if (strpos($condition, 'product') !== false || 
                strpos($condition, 'shop') !== false || 
                strpos($condition, 'woocommerce') !== false) {
                return true;
            }
        }
        
        // Check template title
        $title_lower = strtolower($template->post_title);
        $wc_keywords = array('shop', 'product', 'cart', 'checkout', 'woocommerce');
        
        foreach ($wc_keywords as $keyword) {
            if (strpos($title_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get template key for grouping
     */
    private function get_template_key($template, $conditions) {
        $template_type = get_post_meta($template->ID, '_elementor_template_type', true);
        $key = $template_type;
        
        // Add specific conditions to key
        foreach ($conditions as $condition) {
            if (strpos($condition, 'product_cat') !== false) {
                $key .= '_category';
                break;
            } elseif (strpos($condition, 'product_tag') !== false) {
                $key .= '_tag';
                break;
            } elseif (strpos($condition, 'shop') !== false) {
                $key .= '_shop';
                break;
            }
        }
        
        return $key . '_' . sanitize_title($template->post_title);
    }
    
    /**
     * Format conditions for display
     */
    private function format_conditions($conditions) {
        if (empty($conditions)) {
            return __('Keine Bedingungen', 'wc-polylang-integration');
        }
        
        $formatted = array();
        foreach ($conditions as $condition) {
            $formatted[] = str_replace('_', ' ', $condition);
        }
        
        return implode(', ', $formatted);
    }
    
    /**
     * Get template type label
     */
    private function get_template_type_label($type) {
        $labels = array(
            'archive' => __('Archiv', 'wc-polylang-integration'),
            'single-product' => __('Einzelprodukt', 'wc-polylang-integration'),
            'product-archive' => __('Produkt-Archiv', 'wc-polylang-integration'),
            'page' => __('Seite', 'wc-polylang-integration')
        );
        
        return isset($labels[$type]) ? $labels[$type] : $type;
    }
    
    /**
     * Handle template duplication and management
     */
    public function duplicate_templates() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_elementor_templates')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $action = sanitize_text_field($_POST['template_action']);
        $results = array();
        
        switch ($action) {
            case 'duplicate_all':
                $results = $this->duplicate_all_templates();
                break;
            case 'duplicate_single':
                $template_id = intval($_POST['template_id']);
                $results = $this->duplicate_single_template($template_id);
                break;
            case 'sync_content':
                $results = $this->sync_all_content();
                break;
            case 'sync_single':
                $de_id = intval($_POST['de_id']);
                $en_id = intval($_POST['en_id']);
                $results = $this->sync_single_content($de_id, $en_id);
                break;
            case 'setup_conditions':
                $results = $this->setup_all_conditions();
                break;
        }
        
        $message = $this->format_results_message($results, $action);
        wp_send_json_success(array('message' => $message));
    }
    
    /**
     * Duplicate all templates
     */
    private function duplicate_all_templates() {
        $results = array();
        $templates = $this->get_woocommerce_templates();
        
        foreach ($templates as $template_data) {
            if ($template_data['de'] && !$template_data['en']) {
                $result = $this->duplicate_single_template($template_data['de']['id']);
                $results = array_merge($results, $result);
            }
        }
        
        return $results;
    }
    
    /**
     * Duplicate single template
     */
    private function duplicate_single_template($template_id) {
        $results = array();
        
        try {
            $original_template = get_post($template_id);
            if (!$original_template) {
                throw new Exception('Template not found');
            }
            
            // Create English version
            $en_template_data = array(
                'post_title' => $original_template->post_title . ' (EN)',
                'post_content' => $original_template->post_content,
                'post_status' => 'publish',
                'post_type' => 'elementor_library',
                'post_author' => $original_template->post_author
            );
            
            $en_template_id = wp_insert_post($en_template_data);
            
            if ($en_template_id && !is_wp_error($en_template_id)) {
                // Copy all meta data
                $meta_data = get_post_meta($template_id);
                foreach ($meta_data as $key => $values) {
                    foreach ($values as $value) {
                        add_post_meta($en_template_id, $key, maybe_unserialize($value));
                    }
                }
                
                // Set languages
                if (function_exists('pll_set_post_language')) {
                    pll_set_post_language($template_id, 'de');
                    pll_set_post_language($en_template_id, 'en');
                    
                    // Link translations
                    if (function_exists('pll_save_post_translations')) {
                        pll_save_post_translations(array(
                            'de' => $template_id,
                            'en' => $en_template_id
                        ));
                    }
                }
                
                // Update conditions for English template
                $this->update_template_conditions($en_template_id, 'en');
                
                $results[] = sprintf(__('✓ Template "%s" erfolgreich dupliziert (ID: %d)', 'wc-polylang-integration'), 
                    $original_template->post_title, $en_template_id);
                
            } else {
                throw new Exception('Failed to create English template');
            }
            
        } catch (Exception $e) {
            $results[] = sprintf(__('✗ Fehler beim Duplizieren von Template %d: %s', 'wc-polylang-integration'), 
                $template_id, $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Update template conditions for language
     */
    private function update_template_conditions($template_id, $language) {
        $conditions = get_post_meta($template_id, '_elementor_conditions', true);
        
        if (!is_array($conditions)) {
            $conditions = array();
        }
        
        // Add language condition
        $conditions[] = 'polylang_language_' . $language;
        
        update_post_meta($template_id, '_elementor_conditions', $conditions);
    }
    
    /**
     * Sync all content
     */
    private function sync_all_content() {
        $results = array();
        $templates = $this->get_woocommerce_templates();
        
        foreach ($templates as $template_data) {
            if ($template_data['de'] && $template_data['en']) {
                $result = $this->sync_single_content($template_data['de']['id'], $template_data['en']['id']);
                $results = array_merge($results, $result);
            }
        }
        
        return $results;
    }
    
    /**
     * Sync single content
     */
    private function sync_single_content($de_id, $en_id) {
        $results = array();
        
        try {
            $de_template = get_post($de_id);
            $en_template = get_post($en_id);
            
            if (!$de_template || !$en_template) {
                throw new Exception('Templates not found');
            }
            
            // Update English template structure (keep content, update structure)
            $elementor_data = get_post_meta($de_id, '_elementor_data', true);
            if ($elementor_data) {
                update_post_meta($en_id, '_elementor_data', $elementor_data);
            }
            
            // Update other Elementor meta
            $elementor_meta_keys = array(
                '_elementor_version',
                '_elementor_template_type',
                '_elementor_edit_mode',
                '_elementor_css'
            );
            
            foreach ($elementor_meta_keys as $meta_key) {
                $meta_value = get_post_meta($de_id, $meta_key, true);
                if ($meta_value) {
                    update_post_meta($en_id, $meta_key, $meta_value);
                }
            }
            
            $results[] = sprintf(__('✓ Template-Struktur synchronisiert: %s → %s', 'wc-polylang-integration'), 
                $de_template->post_title, $en_template->post_title);
                
        } catch (Exception $e) {
            $results[] = sprintf(__('✗ Fehler beim Synchronisieren: %s', 'wc-polylang-integration'), $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Setup all conditions
     */
    private function setup_all_conditions() {
        $results = array();
        $templates = $this->get_woocommerce_templates();
        
        foreach ($templates as $template_data) {
            if ($template_data['de']) {
                $this->update_template_conditions($template_data['de']['id'], 'de');
                $results[] = sprintf(__('✓ Deutsche Bedingungen gesetzt für: %s', 'wc-polylang-integration'), 
                    $template_data['de']['title']);
            }
            
            if ($template_data['en']) {
                $this->update_template_conditions($template_data['en']['id'], 'en');
                $results[] = sprintf(__('✓ Englische Bedingungen gesetzt für: %s', 'wc-polylang-integration'), 
                    $template_data['en']['title']);
            }
        }
        
        return $results;
    }
    
    /**
     * Format results message
     */
    private function format_results_message($results, $action) {
        $action_labels = array(
            'duplicate_all' => __('Template-Duplikation', 'wc-polylang-integration'),
            'duplicate_single' => __('Einzelne Template-Duplikation', 'wc-polylang-integration'),
            'sync_content' => __('Content-Synchronisation', 'wc-polylang-integration'),
            'sync_single' => __('Einzelne Content-Synchronisation', 'wc-polylang-integration'),
            'setup_conditions' => __('Bedingungen-Setup', 'wc-polylang-integration')
        );
        
        $title = isset($action_labels[$action]) ? $action_labels[$action] : $action;
        
        $message = '<div class="notice notice-success">';
        $message .= '<h3>🎨 ' . $title . ' abgeschlossen!</h3>';
        
        if (!empty($results)) {
            $message .= '<ul><li>' . implode('</li><li>', $results) . '</li></ul>';
        } else {
            $message .= '<p>' . __('Keine Aktionen erforderlich - alle Templates sind bereits konfiguriert.', 'wc-polylang-integration') . '</p>';
        }
        
        $message .= '<p><strong>' . __('Die Seite wird in 3 Sekunden neu geladen...', 'wc-polylang-integration') . '</strong></p>';
        $message .= '</div>';
        
        return $message;
    }
    
    /**
     * Handle template save
     */
    public function handle_template_save($template_id, $template_data) {
        // Auto-sync when German template is saved
        if (function_exists('pll_get_post_language')) {
            $language = pll_get_post_language($template_id);
            
            if ($language === 'de') {
                // Find English version and sync
                if (function_exists('pll_get_post')) {
                    $en_template_id = pll_get_post($template_id, 'en');
                    if ($en_template_id) {
                        // Schedule sync in background
                        wp_schedule_single_event(time() + 10, 'wc_polylang_sync_template', array($template_id, $en_template_id));
                    }
                }
            }
        }
    }
    
    /**
     * Filter location templates
     */
    public function filter_location_templates($templates, $location) {
        if (!function_exists('pll_current_language')) {
            return $templates;
        }
        
        $current_language = pll_current_language();
        
        // Filter templates by current language
        $filtered_templates = array();
        
        foreach ($templates as $template) {
            $template_language = function_exists('pll_get_post_language') ? pll_get_post_language($template['template_id']) : null;
            
            // Include template if it matches current language or has no language set
            if (!$template_language || $template_language === $current_language) {
                $filtered_templates[] = $template;
            }
        }
        
        return $filtered_templates;
    }
    
    /**
     * Modify WooCommerce widgets
     */
    public function modify_woocommerce_widgets() {
        // This will be extended to modify Elementor WooCommerce widgets for multilingual support
        // For now, we ensure they work with the current language context
        
        if (function_exists('pll_current_language')) {
            $current_language = pll_current_language();
            
            // Set language context for WooCommerce widgets
            add_filter('woocommerce_widget_cart_is_hidden', function($is_hidden) use ($current_language) {
                // Ensure cart widget works in all languages
                return $is_hidden;
            });
        }
    }
}

// Schedule template sync action
add_action('wc_polylang_sync_template', function($de_id, $en_id) {
    $elementor = WC_Polylang_Elementor::get_instance();
    if ($elementor) {
        $elementor->sync_single_content($de_id, $en_id);
    }
}, 10, 2);
