<?php
/**
 * Shop page language configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Polylang_Shop_Config {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (!is_admin()) {
            return;
        }
        
        add_action('admin_menu', array($this, 'add_config_page'));
        add_action('wp_ajax_wc_polylang_setup_shop_pages', array($this, 'setup_shop_pages'));
    }
    
    /**
     * Add configuration page
     */
    public function add_config_page() {
        add_submenu_page(
            'wc-polylang-integration',
            __('Shop-Seiten Konfiguration', 'wc-polylang-integration'),
            __('Shop-Seiten', 'wc-polylang-integration'),
            'manage_woocommerce',
            'wc-polylang-shop-config',
            array($this, 'config_page')
        );
    }
    
    /**
     * Configuration page
     */
    public function config_page() {
        $languages = function_exists('pll_languages_list') ? pll_languages_list() : array();
        $shop_pages = $this->get_shop_pages_status();
        
        ?>
        <div class="wrap">
            <h1><?php _e('WooCommerce Shop-Seiten Konfiguration', 'wc-polylang-integration'); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('Hier können Sie die mehrsprachigen Shop-Seiten automatisch einrichten.', 'wc-polylang-integration'); ?></p>
            </div>
            
            <div class="wc-polylang-shop-status">
                <h2><?php _e('Aktueller Status', 'wc-polylang-integration'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Seite', 'wc-polylang-integration'); ?></th>
                            <th><?php _e('Deutsch', 'wc-polylang-integration'); ?></th>
                            <th><?php _e('Englisch', 'wc-polylang-integration'); ?></th>
                            <th><?php _e('Status', 'wc-polylang-integration'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shop_pages as $page_type => $page_data): ?>
                        <tr>
                            <td><strong><?php echo esc_html($page_data['label']); ?></strong></td>
                            <td>
                                <?php if ($page_data['de']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                    <a href="<?php echo get_edit_post_link($page_data['de']['id']); ?>" target="_blank">
                                        <?php echo esc_html($page_data['de']['title']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no-alt" style="color: red;"></span>
                                    <?php _e('Nicht gefunden', 'wc-polylang-integration'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($page_data['en']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                    <a href="<?php echo get_edit_post_link($page_data['en']['id']); ?>" target="_blank">
                                        <?php echo esc_html($page_data['en']['title']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no-alt" style="color: red;"></span>
                                    <?php _e('Nicht erstellt', 'wc-polylang-integration'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($page_data['de'] && $page_data['en']): ?>
                                    <span class="status-complete"><?php _e('✓ Vollständig', 'wc-polylang-integration'); ?></span>
                                <?php else: ?>
                                    <span class="status-incomplete"><?php _e('⚠ Unvollständig', 'wc-polylang-integration'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="wc-polylang-actions">
                <h2><?php _e('Aktionen', 'wc-polylang-integration'); ?></h2>
                <p><?php _e('Klicken Sie auf "Shop-Seiten einrichten", um automatisch alle fehlenden englischen Seiten zu erstellen.', 'wc-polylang-integration'); ?></p>
                
                <button type="button" id="setup-shop-pages" class="button button-primary button-large">
                    <?php _e('🛍️ Shop-Seiten automatisch einrichten', 'wc-polylang-integration'); ?>
                </button>
                
                <div id="setup-progress" style="display: none; margin-top: 20px;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <p id="progress-text"><?php _e('Einrichtung läuft...', 'wc-polylang-integration'); ?></p>
                </div>
                
                <div id="setup-results" style="display: none; margin-top: 20px;"></div>
            </div>
            
            <div class="wc-polylang-manual">
                <h2><?php _e('Manuelle Konfiguration', 'wc-polylang-integration'); ?></h2>
                <div class="manual-steps">
                    <h3><?php _e('So richten Sie Shop-Seiten manuell ein:', 'wc-polylang-integration'); ?></h3>
                    <ol>
                        <li><?php _e('Gehen Sie zu <strong>Seiten → Alle Seiten</strong>', 'wc-polylang-integration'); ?></li>
                        <li><?php _e('Finden Sie die <strong>Shop-Seite</strong> und klicken Sie auf "Bearbeiten"', 'wc-polylang-integration'); ?></li>
                        <li><?php _e('In der <strong>Polylang-Box</strong> (rechts): Sprache auf "Deutsch" setzen', 'wc-polylang-integration'); ?></li>
                        <li><?php _e('Klicken Sie auf das <strong>"+" bei English</strong> um die englische Version zu erstellen', 'wc-polylang-integration'); ?></li>
                        <li><?php _e('Titel der englischen Seite anpassen (z.B. "Shop")', 'wc-polylang-integration'); ?></li>
                        <li><?php _e('Wiederholen Sie dies für Warenkorb, Kasse und Mein Konto', 'wc-polylang-integration'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
        
        <style>
        .wc-polylang-shop-status {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
        }
        .status-complete {
            color: #46b450;
            font-weight: bold;
        }
        .status-incomplete {
            color: #dc3232;
            font-weight: bold;
        }
        .wc-polylang-actions {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
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
        .wc-polylang-manual {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
        }
        .manual-steps ol {
            padding-left: 20px;
        }
        .manual-steps li {
            margin-bottom: 10px;
            line-height: 1.5;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#setup-shop-pages').on('click', function() {
                var button = $(this);
                var progress = $('#setup-progress');
                var results = $('#setup-results');
                var progressFill = $('.progress-fill');
                var progressText = $('#progress-text');
                
                button.prop('disabled', true);
                progress.show();
                results.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_setup_shop_pages',
                        nonce: '<?php echo wp_create_nonce('wc_polylang_setup_shop_pages'); ?>'
                    },
                    success: function(response) {
                        progressFill.css('width', '100%');
                        progressText.text('<?php _e('Einrichtung abgeschlossen!', 'wc-polylang-integration'); ?>');
                        
                        setTimeout(function() {
                            progress.hide();
                            results.html(response.data.message).show();
                            button.prop('disabled', false);
                            
                            // Reload page after 2 seconds
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        }, 1000);
                    },
                    error: function() {
                        progress.hide();
                        results.html('<div class="notice notice-error"><p><?php _e('Fehler bei der Einrichtung. Bitte versuchen Sie es erneut.', 'wc-polylang-integration'); ?></p></div>').show();
                        button.prop('disabled', false);
                    }
                });
                
                // Simulate progress
                var width = 0;
                var interval = setInterval(function() {
                    width += Math.random() * 30;
                    if (width > 90) width = 90;
                    progressFill.css('width', width + '%');
                }, 200);
                
                setTimeout(function() {
                    clearInterval(interval);
                }, 3000);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get shop pages status
     */
    private function get_shop_pages_status() {
        $pages = array(
            'shop' => array(
                'label' => __('Shop', 'wc-polylang-integration'),
                'wc_page' => 'shop'
            ),
            'cart' => array(
                'label' => __('Warenkorb', 'wc-polylang-integration'),
                'wc_page' => 'cart'
            ),
            'checkout' => array(
                'label' => __('Kasse', 'wc-polylang-integration'),
                'wc_page' => 'checkout'
            ),
            'myaccount' => array(
                'label' => __('Mein Konto', 'wc-polylang-integration'),
                'wc_page' => 'myaccount'
            )
        );
        
        foreach ($pages as $key => &$page) {
            $page_id = wc_get_page_id($page['wc_page']);
            
            // German version
            if ($page_id && $page_id > 0) {
                $page['de'] = array(
                    'id' => $page_id,
                    'title' => get_the_title($page_id)
                );
                
                // English version
                if (function_exists('pll_get_post')) {
                    $en_page_id = pll_get_post($page_id, 'en');
                    if ($en_page_id) {
                        $page['en'] = array(
                            'id' => $en_page_id,
                            'title' => get_the_title($en_page_id)
                        );
                    } else {
                        $page['en'] = false;
                    }
                } else {
                    $page['en'] = false;
                }
            } else {
                $page['de'] = false;
                $page['en'] = false;
            }
        }
        
        return $pages;
    }
    
    /**
     * Setup shop pages automatically
     */
    public function setup_shop_pages() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_polylang_setup_shop_pages')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $results = array();
        $pages_created = 0;
        
        $wc_pages = array(
            'shop' => __('Shop', 'wc-polylang-integration'),
            'cart' => __('Cart', 'wc-polylang-integration'),
            'checkout' => __('Checkout', 'wc-polylang-integration'),
            'myaccount' => __('My Account', 'wc-polylang-integration')
        );
        
        foreach ($wc_pages as $page_key => $en_title) {
            $page_id = wc_get_page_id($page_key);
            
            if ($page_id && $page_id > 0) {
                // Set German language for original page
                if (function_exists('pll_set_post_language')) {
                    pll_set_post_language($page_id, 'de');
                }
                
                // Check if English version exists
                if (function_exists('pll_get_post')) {
                    $en_page_id = pll_get_post($page_id, 'en');
                    
                    if (!$en_page_id) {
                        // Create English version
                        $original_page = get_post($page_id);
                        
                        $en_page_data = array(
                            'post_title' => $en_title,
                            'post_content' => $original_page->post_content,
                            'post_status' => 'publish',
                            'post_type' => 'page',
                            'post_slug' => sanitize_title($en_title)
                        );
                        
                        $en_page_id = wp_insert_post($en_page_data);
                        
                        if ($en_page_id && !is_wp_error($en_page_id)) {
                            // Set English language
                            if (function_exists('pll_set_post_language')) {
                                pll_set_post_language($en_page_id, 'en');
                            }
                            
                            // Link translations
                            if (function_exists('pll_save_post_translations')) {
                                pll_save_post_translations(array(
                                    'de' => $page_id,
                                    'en' => $en_page_id
                                ));
                            }
                            
                            $results[] = sprintf(__('✓ Englische %s-Seite erstellt', 'wc-polylang-integration'), $en_title);
                            $pages_created++;
                        } else {
                            $results[] = sprintf(__('✗ Fehler beim Erstellen der englischen %s-Seite', 'wc-polylang-integration'), $en_title);
                        }
                    } else {
                        $results[] = sprintf(__('→ Englische %s-Seite existiert bereits', 'wc-polylang-integration'), $en_title);
                    }
                }
            } else {
                $results[] = sprintf(__('✗ %s-Seite nicht gefunden', 'wc-polylang-integration'), $en_title);
            }
        }
        
        $message = '<div class="notice notice-success"><h3>' . __('Shop-Seiten Einrichtung abgeschlossen!', 'wc-polylang-integration') . '</h3>';
        $message .= '<p>' . sprintf(__('%d neue Seiten erstellt.', 'wc-polylang-integration'), $pages_created) . '</p>';
        $message .= '<ul><li>' . implode('</li><li>', $results) . '</li></ul>';
        $message .= '<p><strong>' . __('Die Seite wird in 2 Sekunden neu geladen...', 'wc-polylang-integration') . '</strong></p></div>';
        
        wp_send_json_success(array('message' => $message));
    }
}
