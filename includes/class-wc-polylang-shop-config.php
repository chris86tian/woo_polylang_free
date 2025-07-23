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
            __('🛍️ Shop-Seiten', 'wc-polylang-integration'),
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
            <h1>🛍️ WooCommerce Shop-Seiten Konfiguration</h1>
            <p class="description">Entwickelt von <strong><a href="https://www.lipalife.de" target="_blank">LipaLIFE</a></strong> - Automatische mehrsprachige Shop-Seiten</p>
            
            <div class="notice notice-info">
                <p><strong>📋 Anleitung:</strong> Hier können Sie die mehrsprachigen Shop-Seiten automatisch einrichten und konfigurieren.</p>
                <p>Das System erkennt Ihre vorhandenen deutschen Seiten und erstellt automatisch die englischen Versionen.</p>
            </div>
            
            <div class="wc-polylang-shop-status">
                <h2>📊 Aktueller Status der Shop-Seiten</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 20%;"><?php _e('Seite', 'wc-polylang-integration'); ?></th>
                            <th style="width: 30%;"><?php _e('🇩🇪 Deutsch', 'wc-polylang-integration'); ?></th>
                            <th style="width: 30%;"><?php _e('🇬🇧 English', 'wc-polylang-integration'); ?></th>
                            <th style="width: 20%;"><?php _e('Status', 'wc-polylang-integration'); ?></th>
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
                                    <br><small style="color: #666;">ID: <?php echo $page_data['de']['id']; ?> | Slug: <?php echo $page_data['de']['slug']; ?></small>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no-alt" style="color: red;"></span>
                                    <span style="color: red;"><?php _e('Nicht gefunden', 'wc-polylang-integration'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($page_data['en']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                    <a href="<?php echo get_edit_post_link($page_data['en']['id']); ?>" target="_blank">
                                        <?php echo esc_html($page_data['en']['title']); ?>
                                    </a>
                                    <br><small style="color: #666;">ID: <?php echo $page_data['en']['id']; ?> | Slug: <?php echo $page_data['en']['slug']; ?></small>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no-alt" style="color: red;"></span>
                                    <span style="color: red;"><?php _e('Nicht erstellt', 'wc-polylang-integration'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($page_data['de'] && $page_data['en']): ?>
                                    <span class="status-complete">✅ Vollständig</span>
                                <?php elseif ($page_data['de']): ?>
                                    <span class="status-partial">⚠️ Teilweise</span>
                                <?php else: ?>
                                    <span class="status-incomplete">❌ Fehlt</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="wc-polylang-actions">
                <h2>🚀 Automatische Einrichtung</h2>
                <p><strong>Was passiert beim Klick auf "Shop-Seiten einrichten":</strong></p>
                <ul>
                    <li>✅ Erkennung Ihrer vorhandenen deutschen Shop-Seiten</li>
                    <li>✅ Automatische Erstellung der englischen Versionen</li>
                    <li>✅ Korrekte WooCommerce-Seitenzuordnung</li>
                    <li>✅ Polylang-Übersetzungsverknüpfung</li>
                    <li>✅ SEO-optimierte URLs (shop-en, checkout-en, my-account-en)</li>
                </ul>
                
                <div style="margin: 20px 0;">
                    <button type="button" id="setup-shop-pages" class="button button-primary button-large">
                        🛍️ Shop-Seiten automatisch einrichten
                    </button>
                    <button type="button" id="check-wc-settings" class="button button-secondary">
                        🔍 WooCommerce-Einstellungen prüfen
                    </button>
                </div>
                
                <div id="setup-progress" style="display: none; margin-top: 20px;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <p id="progress-text"><?php _e('Einrichtung läuft...', 'wc-polylang-integration'); ?></p>
                </div>
                
                <div id="setup-results" style="display: none; margin-top: 20px;"></div>
            </div>
            
            <div class="wc-polylang-wc-settings">
                <h2>⚙️ WooCommerce-Einstellungen</h2>
                <p>Nach der automatischen Einrichtung sollten diese Einstellungen korrekt sein:</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Shop-Seite (Deutsch)</th>
                        <td>
                            <?php 
                            $shop_page_id = wc_get_page_id('shop');
                            if ($shop_page_id > 0) {
                                $shop_page = get_post($shop_page_id);
                                echo '<strong>' . esc_html($shop_page->post_title) . '</strong> (ID: ' . $shop_page_id . ')';
                                echo '<br><a href="' . admin_url('admin.php?page=wc-settings&tab=products&section=display') . '">WooCommerce → Einstellungen → Produkte</a>';
                            } else {
                                echo '<span style="color: red;">Nicht konfiguriert</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Warenkorb-Seite</th>
                        <td>
                            <?php 
                            $cart_page_id = wc_get_page_id('cart');
                            if ($cart_page_id > 0) {
                                $cart_page = get_post($cart_page_id);
                                echo '<strong>' . esc_html($cart_page->post_title) . '</strong> (ID: ' . $cart_page_id . ')';
                            } else {
                                echo '<span style="color: red;">Nicht konfiguriert</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Kassen-Seite</th>
                        <td>
                            <?php 
                            $checkout_page_id = wc_get_page_id('checkout');
                            if ($checkout_page_id > 0) {
                                $checkout_page = get_post($checkout_page_id);
                                echo '<strong>' . esc_html($checkout_page->post_title) . '</strong> (ID: ' . $checkout_page_id . ')';
                            } else {
                                echo '<span style="color: red;">Nicht konfiguriert</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Mein Konto-Seite</th>
                        <td>
                            <?php 
                            $myaccount_page_id = wc_get_page_id('myaccount');
                            if ($myaccount_page_id > 0) {
                                $myaccount_page = get_post($myaccount_page_id);
                                echo '<strong>' . esc_html($myaccount_page->post_title) . '</strong> (ID: ' . $myaccount_page_id . ')';
                            } else {
                                echo '<span style="color: red;">Nicht konfiguriert</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="wc-polylang-manual">
                <h2>📖 Manuelle Konfiguration (falls erforderlich)</h2>
                <div class="manual-steps">
                    <h3>So richten Sie Shop-Seiten manuell ein:</h3>
                    <ol>
                        <li><strong>Seiten → Alle Seiten</strong> aufrufen</li>
                        <li>Ihre <strong>Shop-Seite</strong> finden und bearbeiten</li>
                        <li>In der <strong>Polylang-Box</strong> (rechts): Sprache auf "Deutsch" setzen</li>
                        <li>Auf das <strong>"+" bei English</strong> klicken</li>
                        <li>Englische Version erstellen:
                            <ul>
                                <li>Titel: "Shop" (oder "Products")</li>
                                <li>Slug: "shop-en" oder "shop"</li>
                                <li>Inhalt kopieren</li>
                            </ul>
                        </li>
                        <li>Wiederholen für: <strong>Warenkorb, Kasse, Mein Konto</strong></li>
                        <li><strong>WooCommerce → Einstellungen → Erweitert → Seiten-Setup</strong> prüfen</li>
                    </ol>
                    
                    <h3>Wichtige URLs nach der Einrichtung:</h3>
                    <ul>
                        <li>🇩🇪 Deutscher Shop: <code><?php echo home_url('/shop/'); ?></code></li>
                        <li>🇬🇧 Englischer Shop: <code><?php echo home_url('/en/shop/'); ?></code></li>
                        <li>🇩🇪 Deutsche Kasse: <code><?php echo home_url('/kasse/'); ?></code></li>
                        <li>🇬🇧 Englische Kasse: <code><?php echo home_url('/en/checkout/'); ?></code></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <style>
        .wc-polylang-shop-status,
        .wc-polylang-actions,
        .wc-polylang-wc-settings,
        .wc-polylang-manual {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .status-complete {
            color: #46b450;
            font-weight: bold;
        }
        .status-partial {
            color: #ffb900;
            font-weight: bold;
        }
        .status-incomplete {
            color: #dc3232;
            font-weight: bold;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f1f1f1;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa, #00a0d2);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .manual-steps ol {
            padding-left: 20px;
        }
        .manual-steps li {
            margin-bottom: 10px;
            line-height: 1.5;
        }
        .manual-steps ul {
            margin-top: 10px;
        }
        
        .form-table th {
            width: 200px;
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
                
                if (!confirm('Möchten Sie die mehrsprachigen Shop-Seiten automatisch einrichten?\n\nDies erstellt englische Versionen Ihrer deutschen Shop-Seiten.')) {
                    return;
                }
                
                button.prop('disabled', true);
                progress.show();
                results.hide();
                
                // Simulate progress
                var width = 0;
                var interval = setInterval(function() {
                    width += Math.random() * 20;
                    if (width > 90) width = 90;
                    progressFill.css('width', width + '%');
                }, 300);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_polylang_setup_shop_pages',
                        nonce: '<?php echo wp_create_nonce('wc_polylang_setup_shop_pages'); ?>'
                    },
                    success: function(response) {
                        clearInterval(interval);
                        progressFill.css('width', '100%');
                        progressText.text('Einrichtung abgeschlossen!');
                        
                        setTimeout(function() {
                            progress.hide();
                            results.html(response.data.message).show();
                            button.prop('disabled', false);
                            
                            // Reload page after 3 seconds
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        }, 1000);
                    },
                    error: function() {
                        clearInterval(interval);
                        progress.hide();
                        results.html('<div class="notice notice-error"><p>Fehler bei der Einrichtung. Bitte versuchen Sie es erneut oder wenden Sie sich an den Support.</p></div>').show();
                        button.prop('disabled', false);
                    }
                });
            });
            
            $('#check-wc-settings').on('click', function() {
                window.open('<?php echo admin_url('admin.php?page=wc-settings&tab=advanced&section=page_setup'); ?>', '_blank');
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
                'label' => __('🛍️ Shop', 'wc-polylang-integration'),
                'wc_page' => 'shop',
                'en_title' => 'Shop'
            ),
            'cart' => array(
                'label' => __('🛒 Warenkorb', 'wc-polylang-integration'),
                'wc_page' => 'cart',
                'en_title' => 'Cart'
            ),
            'checkout' => array(
                'label' => __('💳 Kasse', 'wc-polylang-integration'),
                'wc_page' => 'checkout',
                'en_title' => 'Checkout'
            ),
            'myaccount' => array(
                'label' => __('👤 Mein Konto', 'wc-polylang-integration'),
                'wc_page' => 'myaccount',
                'en_title' => 'My Account'
            )
        );
        
        foreach ($pages as $key => &$page) {
            $page_id = wc_get_page_id($page['wc_page']);
            
            // German version
            if ($page_id && $page_id > 0) {
                $de_page = get_post($page_id);
                $page['de'] = array(
                    'id' => $page_id,
                    'title' => $de_page->post_title,
                    'slug' => $de_page->post_name
                );
                
                // English version
                if (function_exists('pll_get_post')) {
                    $en_page_id = pll_get_post($page_id, 'en');
                    if ($en_page_id) {
                        $en_page = get_post($en_page_id);
                        $page['en'] = array(
                            'id' => $en_page_id,
                            'title' => $en_page->post_title,
                            'slug' => $en_page->post_name
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
        $pages_linked = 0;
        
        $wc_pages = array(
            'shop' => array(
                'en_title' => 'Shop',
                'en_slug' => 'shop'
            ),
            'cart' => array(
                'en_title' => 'Cart',
                'en_slug' => 'cart'
            ),
            'checkout' => array(
                'en_title' => 'Checkout',
                'en_slug' => 'checkout'
            ),
            'myaccount' => array(
                'en_title' => 'My Account',
                'en_slug' => 'my-account'
            )
        );
        
        foreach ($wc_pages as $page_key => $page_config) {
            $page_id = wc_get_page_id($page_key);
            
            if ($page_id && $page_id > 0) {
                // Set German language for original page
                if (function_exists('pll_set_post_language')) {
                    pll_set_post_language($page_id, 'de');
                    $results[] = sprintf('✅ Deutsche %s-Seite als Deutsch markiert', $page_config['en_title']);
                }
                
                // Check if English version exists
                if (function_exists('pll_get_post')) {
                    $en_page_id = pll_get_post($page_id, 'en');
                    
                    if (!$en_page_id) {
                        // Create English version
                        $original_page = get_post($page_id);
                        
                        $en_page_data = array(
                            'post_title' => $page_config['en_title'],
                            'post_content' => $original_page->post_content,
                            'post_status' => 'publish',
                            'post_type' => 'page',
                            'post_name' => $page_config['en_slug'],
                            'post_parent' => 0,
                            'menu_order' => $original_page->menu_order
                        );
                        
                        $en_page_id = wp_insert_post($en_page_data);
                        
                        if ($en_page_id && !is_wp_error($en_page_id)) {
                            // Set English language
                            if (function_exists('pll_set_post_language')) {
                                pll_set_post_language($en_page_id, 'en');
                            }
                            
                            // Copy meta data
                            $meta_keys = get_post_meta($page_id);
                            foreach ($meta_keys as $key => $values) {
                                if (strpos($key, '_') !== 0) continue; // Only copy private meta
                                foreach ($values as $value) {
                                    add_post_meta($en_page_id, $key, maybe_unserialize($value));
                                }
                            }
                            
                            // Link translations
                            if (function_exists('pll_save_post_translations')) {
                                $translations = array(
                                    'de' => $page_id,
                                    'en' => $en_page_id
                                );
                                pll_save_post_translations($translations);
                                $pages_linked++;
                            }
                            
                            $results[] = sprintf('🆕 Englische %s-Seite erstellt (ID: %d)', $page_config['en_title'], $en_page_id);
                            $pages_created++;
                        } else {
                            $error_msg = is_wp_error($en_page_id) ? $en_page_id->get_error_message() : 'Unbekannter Fehler';
                            $results[] = sprintf('❌ Fehler beim Erstellen der englischen %s-Seite: %s', $page_config['en_title'], $error_msg);
                        }
                    } else {
                        // English version exists, just link if not linked
                        if (function_exists('pll_save_post_translations')) {
                            $translations = array(
                                'de' => $page_id,
                                'en' => $en_page_id
                            );
                            pll_save_post_translations($translations);
                            $pages_linked++;
                        }
                        $results[] = sprintf('🔗 Englische %s-Seite existiert bereits und wurde verknüpft', $page_config['en_title']);
                    }
                }
            } else {
                $results[] = sprintf('⚠️ Deutsche %s-Seite nicht in WooCommerce konfiguriert', $page_config['en_title']);
            }
        }
        
        // Flush rewrite rules to ensure URLs work
        flush_rewrite_rules();
        
        $message = '<div class="notice notice-success">';
        $message .= '<h3>🎉 Shop-Seiten Einrichtung abgeschlossen!</h3>';
        $message .= '<p><strong>Zusammenfassung:</strong></p>';
        $message .= '<ul>';
        $message .= '<li>📊 ' . sprintf('%d neue Seiten erstellt', $pages_created) . '</li>';
        $message .= '<li>🔗 ' . sprintf('%d Übersetzungsverknüpfungen erstellt', $pages_linked) . '</li>';
        $message .= '<li>🔄 URL-Regeln aktualisiert</li>';
        $message .= '</ul>';
        
        $message .= '<h4>📋 Details:</h4>';
        $message .= '<ul><li>' . implode('</li><li>', $results) . '</li></ul>';
        
        $message .= '<div style="background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa; margin: 15px 0;">';
        $message .= '<h4>🎯 Nächste Schritte:</h4>';
        $message .= '<ol>';
        $message .= '<li>Prüfen Sie <strong>WooCommerce → Einstellungen → Erweitert → Seiten-Setup</strong></li>';
        $message .= '<li>Testen Sie die URLs: <code>/shop/</code> und <code>/en/shop/</code></li>';
        $message .= '<li>Überprüfen Sie die Sprachweiterleitung</li>';
        $message .= '</ol>';
        $message .= '</div>';
        
        $message .= '<p><strong>Die Seite wird in 3 Sekunden neu geladen...</strong></p>';
        $message .= '</div>';
        
        wp_send_json_success(array('message' => $message));
    }
}

// Initialize the shop config
if (is_admin()) {
    WC_Polylang_Shop_Config::get_instance();
}
