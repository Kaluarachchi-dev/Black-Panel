<?php
/**
 * Plugin Name: Black Panel: WooCommerce Admin Dashboard Theme
 * Description: Beautiful and modern Shopify-style WooCommerce admin panel & dashboard, fully secured for litec.site.
 * Version: 3.1.9
 * Author: Black Panel Team
 * Author URI: https://litec.site/
 * Text Domain: black-panel
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * WC requires at least: 4.0
 * WC tested up to: 9.4
 * Requires PHP: 7.4
 * Network: true
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 **/

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// CONSTANTS (Updated to Black Panel)
// =============================================================================
define('BLACKPANEL_VERSION', '3.1.9');
define('BLACKPANEL_PATH', plugin_dir_path(__FILE__));
define('BLACKPANEL_URL', plugin_dir_url(__FILE__));
define('BLACKPANEL_BASENAME', plugin_basename(__FILE__));

// =============================================================================
// NETWORK ACCESS RULES
// =============================================================================
if (file_exists(BLACKPANEL_PATH . 'includes/brikpanel-network-access.php')) {
    require_once BLACKPANEL_PATH . 'includes/brikpanel-network-access.php';
}

// =============================================================================
// WOOCOMMERCE DEPENDENCY GUARD
// =============================================================================
if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    add_action( 'admin_notices', function () {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        echo '<div class="notice notice-warning black-panel-notice"><p><strong>Black Panel:</strong> '
            . esc_html__( 'WooCommerce is not active on this site, so Black Panel features are disabled here. Activate WooCommerce to enable Black Panel.', 'black-panel' )
            . '</p></div>';
    } );
    return;
}

// =============================================================================
// SEO PLUGIN COMPATIBILITY BOOTSTRAP
// =============================================================================
add_action('plugins_loaded', function () {
    if (!is_admin()) {
        return;
    }
    $page   = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    $action = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';
    $is_editor_page = ($page === 'black-panel-product-editor' || $page === 'brikpanel-product-editor');
    $is_editor_save = (defined('DOING_AJAX') && DOING_AJAX && ($action === 'black-panel_save_product' || $action === 'brikpanel_save_product'));
    if (!$is_editor_page && !$is_editor_save) {
        return;
    }

    $GLOBALS['pagenow'] = 'post.php';
    add_filter('wpseo_always_register_metaboxes_on_admin', '__return_true');

    if ($is_editor_page) {
        $product_id = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;
        if ($product_id) {
            $_GET['post']       = $product_id;
            $_REQUEST['post']   = $product_id;
            $_GET['action']     = 'edit';
            $_REQUEST['action'] = 'edit';
            $_GET['post_type']  = 'product';
        }
    }

    if ($is_editor_save && !empty($_POST['product_id'])) {
        $pid = absint($_POST['product_id']);
        $_POST['ID']        = $pid;
        $_REQUEST['ID']     = $pid;
        $_POST['post_ID']   = $pid;
        $_POST['post_type'] = 'product';
    }
}, 0);

// =============================================================================
// WOOCOMMERCE HPOS COMPATIBILITY
// =============================================================================
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// =============================================================================
// CUSTOM ORDER STATUSES
// =============================================================================
add_action('init', function () {
    register_post_status('wc-return-draft', [
        'label'                     => _x('Return Draft', 'Order status', 'black-panel'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Return Draft <span class="count">(%s)</span>',
            'Return Draft <span class="count">(%s)</span>',
            'black-panel'
        ),
    ]);

    register_post_status('wc-change', [
        'label'                     => _x('Change', 'Order status', 'black-panel'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Change <span class="count">(%s)</span>',
            'Change <span class="count">(%s)</span>',
            'black-panel'
        ),
    ]);
});

add_filter('wc_order_statuses', function ($statuses) {
    if (did_action('init')) {
        $statuses['wc-return-draft'] = _x('Return Draft', 'Order status', 'black-panel');
        $statuses['wc-change']       = _x('Change', 'Order status', 'black-panel');
    } else {
        $statuses['wc-return-draft'] = 'Return Draft';
        $statuses['wc-change']       = 'Change';
    }
    return $statuses;
});

// =============================================================================
// LOAD TEXT DOMAIN
// =============================================================================
function blackpanel_load_textdomain() {
    load_plugin_textdomain('black-panel', false, dirname(BLACKPANEL_BASENAME) . '/languages');
}
add_action('init', 'blackpanel_load_textdomain', 1);

// =============================================================================
// ADMIN SIDE FILES
// =============================================================================
function blackpanel_init_admin() {
    if (!is_admin()) {
        return;
    }

    // Helper checks to load existing internal components safely
    $files = [
        'includes/brikpanel-desktop-mode-compat.php',
        'includes/brikpanel-cache-clear.php',
        'front-end/dashboard/brikpanel-dashboard.php',
        'front-end/dashboard/brikpanel-dashboard-section-order.php',
        'front-end/dashboard/brikpanel-dashboard-topbar.php',
        'front-end/master-switch/brikpanel-master-switch.php',
        'front-end/navigation/brikpanel-nav-customizer.php',
        'front-end/search/brikpanel-search.php',
        'front-end/orders/brikpanel-orders.php',
        'front-end/orders/brikpanel-orders-stats.php',
        'front-end/import-export/brikpanel-import-export.php',
        'front-end/products/brikpanel-section-order.php',
        'front-end/products/brikpanel-qe-order.php',
        'front-end/products/brikpanel-product-editor.php',
        'front-end/products/brikpanel-products-list.php',
        'front-end/products/brikpanel-category-enhancements.php',
        'front-end/coupons/brikpanel-coupons.php',
        'front-end/segments/brikpanel-segments.php',
        'front-end/customer-analytics/brikpanel-customer-analytics.php',
        'front-end/expenses/brikpanel-expenses.php',
        'front-end/vendors/brikpanel-vendors.php',
        'back-end/total-sales/brikpanel-total-sales.php',
        'back-end/conversion-count/brikpanel-total-orders.php',
        'back-end/order-value/brikpanel-order-value.php',
        'back-end/order-rates/brikpanel-order-rates.php'
    ];

    if ( get_option( 'brikpanel_modern_navigation', 'yes' ) !== 'no' ) {
        $files[] = 'front-end/navigation/brikpanel-navigation.php';
    }

    foreach ($files as $file) {
        if (file_exists(BLACKPANEL_PATH . $file)) {
            require_once BLACKPANEL_PATH . $file;
        }
    }
}
add_action('init', 'blackpanel_init_admin');

// =============================================================================
// SUPPRESS THIRD-PARTY ADMIN NOTICES
// =============================================================================
function blackpanel_suppress_foreign_notices() {
    if (!is_admin() || is_network_admin()) {
        return;
    }
    if (get_option('blackpanel_hide_foreign_notices', 'yes') !== 'yes') {
        return;
    }

    $capture = function () { ob_start(); };
    $flush = function () {
        $html = ob_get_clean();
        if ($html === false || $html === '') return;
        
        $opener = '#<div\b[^>]*class="[^"]*\b(?:notice|updated|error)\b[^"]*"[^>]*>#is';
        $offset = 0;
        $out = '';
        while (preg_match($opener, $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $m[0][1];
            $out .= substr($html, $offset, $start - $offset);
            $pos   = $start + strlen($m[0][0]);
            $depth = 1;
            while ($depth > 0 && preg_match('#<(/?)div\b[^>]*>#i', $html, $tag, PREG_OFFSET_CAPTURE, $pos)) {
                $pos = $tag[0][1] + strlen($tag[0][0]);
                if ($tag[1][0] === '/') $depth--;
                else $depth++;
            }
            $block = substr($html, $start, $pos - $start);
            if (strpos($block, 'black-panel-notice') !== false || strpos($block, 'brikpanel-notice') !== false) {
                $out .= $block;
            }
            $offset = $pos;
        }
        $out .= substr($html, $offset);
        echo $out;
    };

    $hooks = ['admin_notices', 'all_admin_notices', 'user_admin_notices'];
    foreach ($hooks as $hook) {
        add_action($hook, $capture, -PHP_INT_MAX);
        add_action($hook, $flush, PHP_INT_MAX);
    }

    add_action('admin_head', function () {
        echo '<style>
            .wp-admin #wpbody-content > .notice:not(.black-panel-notice):not(.brikpanel-notice),
            .wp-admin #wpbody-content > .updated:not(.black-panel-notice):not(.brikpanel-notice),
            .wp-admin #wpbody-content > .error:not(.black-panel-notice):not(.brikpanel-notice) {
                display: none !important;
            }
        </style>';
    }, 9999);
}
add_action('admin_init', 'blackpanel_suppress_foreign_notices');

// =============================================================================
// FRONT-END & GENERAL FILES
// =============================================================================
function blackpanel_init_other() {
    $files = [
        'front-end/login/brikpanel-login.php',
        'front-end/appearance/brikpanel-appearance.php',
        'front-end/products/brikpanel-variation-gallery.php',
        'front-end/products/brikpanel-short-description.php',
        'front-end/sound/brikpanel-sound.php',
        'back-end/conversion-count/brikpanel-conversion-count.php',
        'back-end/conversion-count/brikpanel-product-count.php',
        'back-end/conversion-count/brikpanel-checkout-count.php',
        'back-end/conversion-count/brikpanel-add-to-cart-count.php',
        'back-end/most-count/most-add-to-cart/brikpanel-most-add-to-cart.php',
        'back-end/most-count/most-sale/brikpanel-most-sale.php',
        'back-end/most-count/most-view/brikpanel-most-view.php',
        'back-end/live/brikpanel-live.php',
        'front-end/welcome/brikpanel-welcome.php',
        'includes/brikpanel-helpers.php',
        'includes/brikpanel-profit.php',
        'includes/brikpanel-access-control.php',
        'includes/brikpanel-ase-bridge.php',
        'includes/brikpanel-enqueue.php',
        'includes/brikpanel-hooks-api.php',
        'includes/brikpanel-review-notices.php',
        'includes/cron/brikpanel-cron.php',
        'includes/cron/customer-analytics-jobs.php',
        'front-end/brikcontrol/brikpanel-brikcontrol.php',
        'front-end/google-sheets/brikpanel-google-sheets.php',
        'front-end/ad-platforms/brikpanel-ad-platforms.php'
    ];

    if ( get_option( 'brikpanel_modern_navigation', 'yes' ) !== 'no' ) {
        $files[] = 'front-end/brikpanel-site-submenu.php';
    }

    foreach ($files as $file) {
        if (file_exists(BLACKPANEL_PATH . $file)) {
            require_once BLACKPANEL_PATH . $file;
        }
    }
}
add_action('init', 'blackpanel_init_other');

// Store Summary Injection
if ( is_admin() && file_exists(BLACKPANEL_PATH . 'includes/brikpanel-store-summary.php') ) {
    require_once BLACKPANEL_PATH . 'includes/brikpanel-store-summary.php';
}
