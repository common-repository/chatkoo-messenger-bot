<?php
/**
 * Plugin Name: Woocommerce-Chatkoo Integration
 * Plugin URI: https://www.chatkoo.com
 * Description: Woocommerce integration for Chatkoo
 * Version: 1.0.0
 * Author: Chatkoo, Inc
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /lang
 * Text Domain: chatkoo
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	class WC_Chatkoo_Settings_Tab {
		/**
		 * Bootstraps the class and hooks required actions & filters.
		 *
		 */
		public static function init() {
			add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
			add_action( 'woocommerce_settings_tabs_settings_tab_chatkoo', __CLASS__ . '::settings_tab' );
			add_action( 'woocommerce_update_options_settings_tab_chatkoo', __CLASS__ . '::update_settings' );
			add_action( 'admin_notices' , __CLASS__ . '::check_settings' );
		}

		/**
		 * Add a new settings tab to the WooCommerce settings tabs array.
		 *
		 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
		 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
		 */
		public static function add_settings_tab( $settings_tabs ) {
			$settings_tabs['settings_tab_chatkoo'] = __( 'Chatkoo Settings', 'woocommerce-settings-tab-chatkoo' );
			return $settings_tabs;
		}

		/**
		 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
		 *
		 * @uses woocommerce_admin_fields()
		 * @uses self::get_settings()
		 */
		public static function settings_tab() {
			woocommerce_admin_fields( self::get_settings() );
		}

		/**
		 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
		 *
		 * @uses woocommerce_update_options()
		 * @uses self::get_settings()
		 */
		public static function update_settings() {
			woocommerce_update_options( self::get_settings() );
		}

		public static function check_settings() {
			$woocommerce_id      = get_option( 'wc_settings_tab_chatkoo_woo_id' );
			if ( empty($woocommerce_id) ) {
				?>
				<div class="error notice">
					<p><?php _e( 'Your Chatkoo plugin might not functioning properly. <strong>Please complete all fields before using this plugin.</strong>' , 'chatkoo_error_message' ); ?></p>
				</div>
				<?php
			}
		}


		/**
		 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
		 *
		 * @return array Array of settings for @see woocommerce_admin_fields() function.
		 */
		public static function get_settings() {
			$settings = array(
				'section_title' => array(
					'name'	 => __( 'Chatkoo Settings', 'woocommerce-settings-tab-chatkoo' ),
					'type'	 => 'title',
					'desc'	 => '',
					'id'	   => 'wc_settings_tab_chatkoo_section_title'
				),
				'title' => array(
					'name' => __( 'Woocommerce ID', 'woocommerce-settings-tab-chatkoo' ),
					'type' => 'text',
					'desc' => __( '', 'woocommerce-settings-tab-chatkoo' ),
					'id'   => 'wc_settings_tab_chatkoo_woo_id'
				),
				'section_end' => array(
					'type' => 'sectionend',
					'id'   => 'wc_settings_tab_chatkoo_section_end'
				)
			);
			return apply_filters( 'wc_settings_tab_chatkoo_settings', $settings );
		}
	}
	WC_Chatkoo_Settings_Tab::init();

	/**
	 * Override Woocommerce frontend script to pug Messenger Checkbox plugin in it
	 */
	function override_woo_frontend_scripts() {
		if ( ! wp_script_is( 'jquery', 'done' ) ) {
			wp_enqueue_script( 'jquery' );
		}

		global $product;
		$id                      = $product->get_id();
		$item['user_ref']        = time();
		$item['url']             = get_site_url();

		$metadata['name']        = $product->get_name();
		$metadata['price']       = $product->get_price();
		$metadata['description'] = $product->get_description();
		$metadata['product_url'] = get_permalink($id);
		$image_raw               = $product->get_image();
		preg_match( '@src="([^"]+)"@' , $image_raw, $match );
		$metadata['image']       = 'https:' . array_pop($match);
		$metadata['user_ref']    = $item['user_ref'];

		$scripts = '';
		foreach($item as $key => $value){
			$scripts .= 'var ' . $key . '="' . $value . '";';
		}

		$scripts                .= ' var metadata=' . json_encode($metadata) . ';';
		$base_script_url         = 'https://api.chatkoo.com';
		$woocommerce_id          = get_option( 'wc_settings_tab_chatkoo_woo_id' );

		/**
		 * Register javascript file to the Cart frontend
		 */
		wp_register_script( 'chatkoo_script' , $base_script_url . '\/static\/' . $woocommerce_id , array( 'jquery' ) , '1.3' , false );
		wp_enqueue_script( 'chatkoo_script' );
		wp_add_inline_script( 'chatkoo_script' , $scripts, 'before');

	}
	add_action( 'woocommerce_after_single_product_summary' , 'override_woo_frontend_scripts' );

	/**
	 * Add field after order notes to put Messenger user_ref generated previously. This field will be processed
	 * by Woocommerce order meta
	 */
	function user_session_checkout_field( $checkout ) {
		wp_enqueue_style( 'chatkoo-style' , plugin_dir_url(__FILE__) . 'css/style.css' );
		woocommerce_form_field( 'user_session', array(
			'type'	=> 'text',
			'class'	=> array( 'fb-uid-hidden' ),
		), $_COOKIE[ 'user_session' ]);

		wp_register_script( 'checkout_script' , plugin_dir_url(__FILE__) . 'js/checkout.js' , array( 'jquery' ) , '1.3' , true );
		wp_enqueue_script( 'checkout_script' );

	}
	add_action( 'woocommerce_after_order_notes' , 'user_session_checkout_field' );

	/**
	 * Update Woocommerce order meta to add user_ref in it
	 */
	function user_session_checkout_field_update_order_meta( $order_id ) {
		if ( ! empty( $_POST[ 'user_session' ] ) ) {
			update_post_meta( $order_id , 'user_session', sanitize_text_field( $_POST[ 'user_session' ] ) );
		}
		if ( ! empty( $_POST['cart_session'] ) ) {
			update_post_meta( $order_id , 'cart_session', sanitize_text_field( $_POST[ 'cart_session' ] ) );
		}
	}
	add_action( 'woocommerce_checkout_update_order_meta', 'user_session_checkout_field_update_order_meta' );

	/**
	 * Attach updated order meta to webhook response
	 */
	function update_api_order_response( $order_data , $order ) {
		$order_data[ 'user_session' ] = get_post_meta( $order->id , 'user_session' , true );
		$order_data[ 'cart_session' ] = get_post_meta( $order->id , 'cart_session' , true );
		return $order_data;
	}
	add_filter( 'woocommerce_api_order_response' , 'update_api_order_response' , 10 , 3 );
} else {
	add_action( 'admin_notices', 'wc_notice' );
}

function wc_notice() {
	?>
	<div class="error notice">
		<p><?php _e( 'Your Chatkoo plugin might not functioning properly. <strong>Woocommerce plugin is not installed or activated.</strong>' , 'chatkoo_error_message' ); ?></p>
	</div>
	<?php
}

function chatkoo_plugin_action_links( $links ) {
	$links = array_merge( array(
		'<a href="' . esc_url( admin_url( '/admin.php?page=wc-settings&tab=settings_tab_chatkoo' ) ) . '">' . __( 'Settings', 'textdomain' ) . '</a>'
	), $links );
	return $links;
}
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'chatkoo_plugin_action_links' );
