<?php
/**
 * Admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_wc_polylang_sync_translations', array($this, 'ajax_sync_translations'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Polylang Integration', 'wc-polylang-integration'),
            __('Polylang Integration', 'wc-polylang-integration'),
            'manage_woocommerce',
            'wc-polylang-integration',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('wc_polylang_settings', 'wc_polylang_enable_product_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_enable_category_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_enable_widget_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_enable_email_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_enable_seo_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_enable_custom_fields_translation');
        register_setting('wc_polylang_settings', 'wc_polylang_default_language');
        register_setting('wc_polylang_settings', 'wc_polylang_seo_canonical_urls');
        register_setting('wc_polylang_settings', 'wc_polylang_seo_hreflang_tags');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('woocommerce_page_wc-polylang-integration' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'wc-polylang-admin',
            WC_POLYLANG_INTEGRATION_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WC_POLYLANG_INTEGRATION_VERSION,
            true
        );
        
        wp_localize_script('wc-polylang-admin', 'wcPolylangAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_polylang_admin'),
            'strings' => array(
                'syncing' => __('Syncing translations...', 'wc-polylang-integration'),
                'syncComplete' => __('Sync completed successfully!', 'wc-polylang-integration'),
                'syncError' => __('Error during sync. Please try again.', 'wc-polylang-integration'),
            )
        ));
        
        wp_enqueue_style(
            'wc-polylang-admin',
            WC_POLYLANG_INTEGRATION_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WC_POLYLANG_INTEGRATION_VERSION
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        $languages = function_exists('pll_languages_list') ? pll_languages_list() : array();
        $stats = $this->get_translation_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('WooCommerce Polylang Integration', 'wc-polylang-integration'); ?></h1>
            
            <div class="wc-polylang-admin-container">
                <!-- Status Dashboard -->
                <div class="wc-polylang-dashboard">
                    <h2><?php _e('Translation Status', 'wc-polylang-integration'); ?></h2>
                    <div class="wc-polylang-stats">
                        <div class="stat-box">
                            <h3><?php echo $stats['products']['total']; ?></h3>
                            <p><?php _e('Total Products', 'wc-polylang-integration'); ?></p>
                        </div>
                        <div class="stat-box">
                            <h3><?php echo $stats['products']['translated']; ?></h3>
                            <p><?php _e('Translated Products', 'wc-polylang-integration'); ?></p>
                        </div>
                        <div class="stat-box">
                            <h3><?php echo $stats['categories']['total']; ?></h3>
                            <p><?php _e('Total Categories', 'wc-polylang-integration'); ?></p>
                        </div>
                        <div class="stat-box">
                            <h3><?php echo $stats['categories']['translated']; ?></h3>
                            <p><?php _e('Translated Categories', 'wc-polylang-integration'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Form -->
                <form method="post" action="options.php" class="wc-polylang-settings-form">
                    <?php settings_fields('wc_polylang_settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Default Language', 'wc-polylang-integration'); ?></th>
                            <td>
                                <select name="wc_polylang_default_language">
                                    <?php foreach ($languages as $lang): ?>
                                        <option value="<?php echo esc_attr($lang); ?>" <?php selected(get_option('wc_polylang_default_language'), $lang); ?>>
                                            <?php echo esc_html(strtoupper($lang)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Translation Features', 'wc-polylang-integration'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="wc_polylang_enable_product_translation" value="yes" <?php checked(get_option('wc_polylang_enable_product_translation'), 'yes'); ?>>
                                        <?php _e('Enable Product Translation', 'wc-polylang-integration'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="wc_polylang_enable_category_translation" value="yes" <?php checked(get_option('wc_polylang_enable_category_translation'), 'yes'); ?>>
                                        <?php _e('Enable Category Translation', 'wc-polylang-integration'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="wc_polylang_enable_widget_translation" value="yes" <?php checked(get_option('wc_polylang_enable_widget_translation'), 'yes'); ?>>
                                        <?php _e('Enable Widget Translation', 'wc-polylang-integration'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="wc_polylang_enable_email_translation" value="yes" <?php checked(get_option('wc_polylang_enable_email_translation'), 'yes'); ?>>
                                        <?php _e('Enable Email Translation', 'wc-polylang-integration'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="wc_polylang_enable_custom_fields_translation" value="yes" <?php checked(get_option('wc_polylang_enable_custom_fields_translation'), 'yes'); ?>>
                                        <?php _e('Enable Custom Fields Translation', 'wc-polylang-integration'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('SEO Features', 'wc-polylang-integration'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="wc_polylang_seo_canonical_urls" value="yes" <?php checked(get_option('wc_polylang_seo_canonical_urls'), 'yes'); ?>>
                                        <?php _e('Enable Canonical URLs', 'wc-polylang-integration'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="wc_polylang_seo_hreflang_tags" value="yes" <?php checked(get_option('wc_polylang_seo_hreflang_tags'), 'yes'); ?>>
                                        <?php _e('Enable hreflang Tags', 'wc-polylang-integration'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
                
                <!-- Sync Tools -->
                <div class="wc-polylang-tools">
                    <h2><?php _e('Translation Tools', 'wc-polylang-integration'); ?></h2>
                    <p><?php _e('Use these tools to manage your translations.', 'wc-polylang-integration'); ?></p>
                    
                    <button type="button" class="button button-primary" id="sync-translations">
                        <?php _e('Sync All Translations', 'wc-polylang-integration'); ?>
                    </button>
                    
                    <div id="sync-progress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <p class="progress-text"></p>
                    </div>
                </div>
                
                <!-- Language Status -->
                <div class="wc-polylang-language-status">
                    <h2><?php _e('Language Status', 'wc-polylang-integration'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Language', 'wc-polylang-integration'); ?></th>
                                <th><?php _e('Products', 'wc-polylang-integration'); ?></th>
                                <th><?php _e('Categories', 'wc-polylang-integration'); ?></th>
                                <th><?php _e('Status', 'wc-polylang-integration'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($languages as $lang): ?>
                                <?php $lang_stats = $this->get_language_stats($lang); ?>
                                <tr>
                                    <td><strong><?php echo esc_html(strtoupper($lang)); ?></strong></td>
                                    <td><?php echo $lang_stats['products']; ?></td>
                                    <td><?php echo $lang_stats['categories']; ?></td>
                                    <td>
                                        <?php if ($lang_stats['completion'] >= 80): ?>
                                            <span class="status-complete"><?php _e('Complete', 'wc-polylang-integration'); ?></span>
                                        <?php elseif ($lang_stats['completion'] >= 50): ?>
                                            <span class="status-partial"><?php _e('Partial', 'wc-polylang-integration'); ?></span>
                                        <?php else: ?>
                                            <span class="status-incomplete"><?php _e('Incomplete', 'wc-polylang-integration'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get translation statistics
     */
    private function get_translation_stats() {
        $stats = array(
            'products' => array(
                'total' => wp_count_posts('product')->publish,
                'translated' => 0
            ),
            'categories' => array(
                'total' => wp_count_terms('product_cat'),
                'translated' => 0
            )
        );
        
        // Count translated products
        if (function_exists('pll_get_post_translations')) {
            $products = get_posts(array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ));
            
            foreach ($products as $product_id) {
                $translations = pll_get_post_translations($product_id);
                if (count($translations) > 1) {
                    $stats['products']['translated']++;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Get language-specific statistics
     */
    private function get_language_stats($language) {
        $stats = array(
            'products' => 0,
            'categories' => 0,
            'completion' => 0
        );
        
        if (function_exists('pll_get_post_translations')) {
            // Count products in this language
            $products = get_posts(array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids',
                'lang' => $language
            ));
            $stats['products'] = count($products);
            
            // Count categories in this language
            $categories = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'lang' => $language
            ));
            $stats['categories'] = count($categories);
            
            // Calculate completion percentage
            $total_products = wp_count_posts('product')->publish;
            $total_categories = wp_count_terms('product_cat');
            
            if ($total_products > 0 || $total_categories > 0) {
                $completion = (($stats['products'] + $stats['categories']) / ($total_products + $total_categories)) * 100;
                $stats['completion'] = round($completion);
            }
        }
        
        return $stats;
    }
    
    /**
     * AJAX sync translations
     */
    public function ajax_sync_translations() {
        check_ajax_referer('wc_polylang_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $result = $this->sync_all_translations();
        
        wp_send_json_success($result);
    }
    
    /**
     * Sync all translations
     */
    private function sync_all_translations() {
        $synced = array(
            'products' => 0,
            'categories' => 0,
            'attributes' => 0
        );
        
        // Sync products
        if (get_option('wc_polylang_enable_product_translation') === 'yes') {
            $products = get_posts(array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ));
            
            foreach ($products as $product_id) {
                if (function_exists('pll_set_post_language')) {
                    $default_lang = get_option('wc_polylang_default_language', 'de');
                    if (!pll_get_post_language($product_id)) {
                        pll_set_post_language($product_id, $default_lang);
                        $synced['products']++;
                    }
                }
            }
        }
        
        // Sync categories
        if (get_option('wc_polylang_enable_category_translation') === 'yes') {
            $categories = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false
            ));
            
            foreach ($categories as $category) {
                if (function_exists('pll_set_term_language')) {
                    $default_lang = get_option('wc_polylang_default_language', 'de');
                    if (!pll_get_term_language($category->term_id)) {
                        pll_set_term_language($category->term_id, $default_lang);
                        $synced['categories']++;
                    }
                }
            }
        }
        
        return $synced;
    }
}
