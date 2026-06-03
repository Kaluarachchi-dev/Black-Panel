<?php
/**
 * Plugin Name:       Black Panel
 * Description:       Advanced WooCommerce Dashboard, Sales Report, Inventory Management & Bulk Editor.
 * Version:           1.0.0
 * Author:            Black Panel Team
 * Text Domain:       black-panel
 * Domain Path:       /languages
 * Requires At Least: 6.0
 * Tested Up To:      7.0
 * Requires PHP:      7.4
 * License:           GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Black_Panel_Main_Setup {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'black_panel_add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'black_panel_enqueue_admin_scripts' ) );
	}

	public function black_panel_add_admin_menu() {
		add_menu_page(
			__( 'Black Panel', 'black-panel' ),
			__( 'Black Panel', 'black-panel' ),
			'manage_woocommerce',
			'black-panel',
			array( $this, 'black_panel_render_admin_page' ),
			'dashicons-dashboard',
			2
		);
	}

	public function black_panel_enqueue_admin_scripts( $hook ) {
		if ( 'toplevel_page_black-panel' !== $hook ) {
			return;
		}

		// Enqueue custom styles for Black Panel
		wp_enqueue_style( 'black-panel-admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css', array(), '1.0.0' );
	}

	public function black_panel_render_admin_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Welcome to Black Panel', 'black-panel' ); ?></h1>
			<p><?php esc_html_e( 'Your customized WooCommerce Dashboard is being prepared.', 'black-panel' ); ?></p>
			
			<div class="black-panel-container" style="padding: 20px; background: #fff; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
				<h3><?php esc_html_e( 'Dashboard Configuration', 'black-panel' ); ?></h3>
				<p><?php esc_html_e( 'Next step: Connect your new secure server domain to load the live analytics charts.', 'black-panel' ); ?></p>
			</div>
		</div>
		<?php
	}
}

// Initialize the plugin
new Black_Panel_Main_Setup();
