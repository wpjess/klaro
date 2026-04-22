<?php
/**
 * Bootstrap hooks.
 *
 * @package Klaro_CMP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Klaro_CMP_Plugin {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		new Klaro_CMP_Settings();
		new Klaro_CMP_Frontend();
		add_filter( 'plugin_action_links', array( $this, 'action_links' ), 10, 2 );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'klaro-cmp', false, dirname( plugin_basename( KLARO_CMP_FILE ) ) . '/languages' );
	}

	/**
	 * @param array<int, string> $links Plugin action links.
	 * @param string             $file  Plugin basename.
	 * @return array<int, string>
	 */
	public function action_links( $links, $file ) {
		if ( plugin_basename( KLARO_CMP_FILE ) !== $file ) {
			return $links;
		}
		$url   = admin_url( 'options-general.php?page=klaro-cmp' );
		$links = (array) $links;
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'klaro-cmp' ) . '</a>' );
		return $links;
	}
}
