<?php
/**
 * Plugin Name: Free Shipping Bar for WooCommerce
 * Plugin URI: https://villatheme.com/
 * Description: Motivate customers to reach the free shipping threshold with a visual free shipping bar, dynamic messages and progress tracker.
 * Version: 1.3.2
 * Author: VillaTheme
 * Author URI: https://villatheme.com
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0
 * Text Domain: woo-free-shipping-bar
 * Domain Path: /languages
 * Copyright 2017 - 2026 VillaTheme.com. All rights reserved.
 * Requires Plugins: woocommerce
 * Requires at least: 5.0
 * Tested up to: 7.0
 * WC requires at least: 7.0
 * WC tested up to: 11.0
 * Requires PHP: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!defined('WFSPB_F_VERSION')){
	define( 'WFSPB_F_VERSION', '1.3.2' );
	define( 'WFSPB_F_SHIPPING_BASENAME', plugin_basename( __FILE__ ) );
	define( 'WFSPB_F_SHIPPING_DIR', plugin_dir_path( __FILE__ ) );
	define( 'WFSPB_F_SHIPPING_INCLUDES', WFSPB_F_SHIPPING_DIR . "includes" . DIRECTORY_SEPARATOR );
	define( 'WFSPB_F_SHIPPING_ADMIN', WFSPB_F_SHIPPING_DIR . "admin" . DIRECTORY_SEPARATOR );
	define( 'WFSPB_F_SHIPPING_FRONTEND', WFSPB_F_SHIPPING_DIR . "frontend" . DIRECTORY_SEPARATOR );
	define( 'WFSPB_F_SHIPPING_LANGUAGES_DIR', WFSPB_F_SHIPPING_DIR . 'languages' . DIRECTORY_SEPARATOR );
	$plugin_url = plugins_url( 'assets/', __FILE__ );
	define( 'WFSPB_F_SHIPPING_CSS', $plugin_url . 'css/' );
	define( 'WFSPB_F_SHIPPING_JS', $plugin_url . 'js/' );
	define( 'WFSPB_F_SHIPPING_IMAGES', $plugin_url . 'images/' );
}
if ( ! class_exists( 'WFSPB_F_Shipping' ) ) {
	/**
	 * Class WFSPB_F_Shipping
	 */
	class WFSPB_F_Shipping {
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'check_environment' ) );
			//Compatible with High-Performance order storage (COT)
			add_action( 'before_woocommerce_init', array( $this, 'before_woocommerce_init' ) );
		}
		public function before_woocommerce_init() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
			}
		}
		public function check_environment() {
			if ( class_exists( 'WFSPB_Shipping' ) ) {
				return;
			}
			if ( ! class_exists( 'VillaTheme_Require_Environment' ) ) {
				include_once WFSPB_F_SHIPPING_INCLUDES . 'support.php';
			}
			$environment = new \VillaTheme_Require_Environment( [
				'plugin_name'     => 'Free Shipping Bar for WooCommerce',
				'php_version'     => '7.0',
				'wp_version'      => '5.0',
				'require_plugins' => [
					[
						'slug' => 'woocommerce',
						'name' => 'WooCommerce',
						'defined_version' => 'WC_VERSION',
						'version' => '7.0',
					],
				],
			] );

			if ( $environment->has_error() ) {
				return;
			}
			$this->includes();
			add_action( 'init', array( $this, 'init' ) );
			add_filter( 'plugin_action_links_' . WFSPB_F_SHIPPING_BASENAME, array( $this, 'settings_link' ) );
		}
		protected function includes() {
			$files = array(
				WFSPB_F_SHIPPING_INCLUDES=>[
					'file_name' => [
						'support.php',
						'data.php',
					]
				],
				WFSPB_F_SHIPPING_ADMIN=>[
					'class_prefix' => 'WFSPB_F_ADMIN_',
					'file_name' => [
						'settings.php'
					]
				],
				WFSPB_F_SHIPPING_FRONTEND=>[
					'class_prefix' => 'WFSPB_F_FRONTEND_',
					'file_name' => [
						'frontend.php',
					]
				]
			);
			foreach ( $files as $path => $items ) {
				if (empty($items['file_name']) || !is_array($items['file_name'])){
					continue;
				}
				$class_prefix = $items['class_prefix']??'';
				foreach ($items['file_name'] as $file_name){
					$file = $path.'/'.$file_name;
					if ( !file_exists( $file ) ) {
						continue;
					}
					require_once $file;
					$ext_file  = pathinfo( $file);
					$class_name = $ext_file['filename'] ??'';
					if ($class_prefix){
						$class_name = preg_replace( '/\W/i', '_', $class_prefix . ucfirst( $class_name ) );
					}
					if ( $class_name && class_exists( $class_name ) ) {
						new $class_name;
					}
				}
			}
		}
		// link setting page on install plugin
		public function settings_link( $links ) {
			$settings_link = '<a href="admin.php?page=woocommerce_free_ship" title="' . esc_html__( 'Settings', 'woo-free-shipping-bar' ) . '">' . esc_html__( 'Settings', 'woo-free-shipping-bar' ) . '</a>';
			array_unshift( $links, $settings_link );

			return $links;
		}
		/**
		 * load Language translate
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woo-free-shipping-bar' );
			load_textdomain( 'woo-free-shipping-bar', WFSPB_F_SHIPPING_LANGUAGES_DIR . "woo-free-shipping-bar-$locale.mo" );
			load_plugin_textdomain( 'woo-free-shipping-bar', false, WFSPB_F_SHIPPING_LANGUAGES_DIR );
		}
		public function init() {
			$this->load_plugin_textdomain();
			if ( class_exists( 'VillaTheme_Support' ) ) {
				new VillaTheme_Support( array(
					'support'    => 'https://wordpress.org/support/plugin/woo-free-shipping-bar',
					'docs'       => 'https://docs.villatheme.com/?item=woocommerce-free-shipping-bar',
					'review'     => 'https://wordpress.org/support/plugin/woo-free-shipping-bar/reviews/?rate=5#rate-response',
					'pro_url'    => 'https://villatheme.com/extensions/woocommerce-free-shipping-bar/',
					'css'        => WFSPB_F_SHIPPING_CSS,
					'image'      => WFSPB_F_SHIPPING_IMAGES,
					'slug'       => 'woo-free-shipping-bar',
					'menu_slug'  => 'woocommerce_free_ship',
					'survey_url' => 'https://script.google.com/macros/s/AKfycbyEruJLWkwB0gXJINPkF8gRKVJ4OulK-F8KfgmWxKPdIXWffVQtC4Rz37mUqKWZo1g-DQ/exec',
					'version'    => WFSPB_F_VERSION,
				) );
			}
		}
	}
	new WFSPB_F_Shipping();
}