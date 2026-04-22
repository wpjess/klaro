<?php
/**
 * Front-end script + inline klaroConfig.
 *
 * @package Klaro_CMP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Klaro_CMP_Frontend {

	const HANDLE        = 'klaro-cmp';
	const CONFIG_HANDLE = 'klaro-cmp-config';

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_filter( 'script_loader_tag', array( $this, 'klaro_script_attributes' ), 10, 2 );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function settings() {
		$o = get_option( Klaro_CMP_Settings::OPTION, Klaro_CMP_Config::defaults() );
		return is_array( $o ) ? array_merge( Klaro_CMP_Config::defaults(), $o ) : Klaro_CMP_Config::defaults();
	}

	/**
	 * Treat common stored forms of the “enabled” flag as on (Settings API / imports can vary).
	 *
	 * @param mixed $val Raw option value.
	 */
	private function is_cmp_enabled( $val ) : bool {
		if ( true === $val || 1 === $val ) {
			return true;
		}
		if ( false === $val || 0 === $val || null === $val ) {
			return false;
		}
		if ( is_string( $val ) ) {
			$val = strtolower( trim( $val ) );
			return in_array( $val, array( '1', 'true', 'yes', 'on' ), true );
		}
		return (bool) $val;
	}

	public function enqueue() {
		if ( is_admin() ) {
			return;
		}

		$s = $this->settings();
		if ( ! $this->is_cmp_enabled( $s['enabled'] ?? false ) ) {
			return;
		}

		if ( ! empty( $s['hide_for_admins'] ) && current_user_can( 'manage_options' ) ) {
			return;
		}

		/**
		 * Short-circuit loading (return false).
		 *
		 * @param bool  $load    Whether to load Klaro.
		 * @param array $settings Merged settings.
		 */
		if ( false === apply_filters( 'klaro_cmp_should_load', true, $s ) ) {
			return;
		}

		$bundled_full = KLARO_CMP_PATH . 'assets/vendor/klaro.js';
		$bundled_nc   = KLARO_CMP_PATH . 'assets/vendor/klaro-no-css.js';

		$config = Klaro_CMP_Config::build( $s );
		$flags  = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		if ( defined( 'JSON_INVALID_UTF8_SUBSTITUTE' ) ) {
			$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
		}
		$json = wp_json_encode( $config, $flags );
		if ( ! is_string( $json ) ) {
			return;
		}

		/*
		 * Register an inline-only script handle so klaroConfig always prints before klaro.js
		 * (dependency order). Loading in the header avoids themes that omit wp_footer().
		 */
		wp_register_script( self::CONFIG_HANDLE, false, array(), KLARO_CMP_VERSION, false );
		wp_enqueue_script( self::CONFIG_HANDLE );
		wp_add_inline_script( self::CONFIG_HANDLE, 'window.klaroConfig=' . $json . ';', 'after' );

		if ( 'cdn' === $s['script_source'] ) {
			$ver = preg_replace( '/^v/', '', (string) $s['cdn_version'] );
			$url = sprintf( 'https://cdn.kiprotect.com/klaro/v%s/klaro.js', $ver );
			wp_enqueue_script( self::HANDLE, $url, array( self::CONFIG_HANDLE ), null, false );
		} elseif ( 'no_css' === $s['bundle'] && is_readable( $bundled_nc ) ) {
			wp_enqueue_style(
				self::HANDLE . '-css',
				KLARO_CMP_URL . 'assets/vendor/klaro.min.css',
				array(),
				KLARO_CMP_VERSION
			);
			wp_enqueue_script(
				self::HANDLE,
				KLARO_CMP_URL . 'assets/vendor/klaro-no-css.js',
				array( self::CONFIG_HANDLE ),
				KLARO_CMP_VERSION,
				false
			);
		} elseif ( is_readable( $bundled_full ) ) {
			wp_enqueue_script(
				self::HANDLE,
				KLARO_CMP_URL . 'assets/vendor/klaro.js',
				array( self::CONFIG_HANDLE ),
				KLARO_CMP_VERSION,
				false
			);
		} else {
			wp_dequeue_script( self::CONFIG_HANDLE );
			return;
		}
	}

	/**
	 * Klaro’s setup() uses document.currentScript (null for deferred scripts) or falls back to
	 * the first script whose URL contains "klaro" — optimizers often break that. Load synchronously
	 * after the inline config (dependency order) so initialization always runs.
	 * Adds data-klaro-config for explicit window property name.
	 *
	 * @param string $tag    HTML.
	 * @param string $handle Handle.
	 * @return string
	 */
	public function klaro_script_attributes( $tag, $handle ) {
		if ( self::HANDLE !== $handle ) {
			return $tag;
		}
		if ( strpos( $tag, 'data-klaro-config=' ) !== false ) {
			return $tag;
		}
		// First <script> only. (str_replace’s 4th arg is a by-reference count, not a limit.)
		return (string) preg_replace( '/<script\\b/i', '<script data-klaro-config="klaroConfig"', $tag, 1 );
	}
}
