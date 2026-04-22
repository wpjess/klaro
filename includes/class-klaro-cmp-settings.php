<?php
/**
 * Settings → Klaro.
 *
 * @package Klaro_CMP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Klaro_CMP_Settings {

	const OPTION = 'klaro_cmp_settings';

	public function __construct() {
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_help_assets' ) );
	}

	/**
	 * Styles and script for JSON field help toggles (Settings → Klaro only).
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_help_assets( $hook_suffix ) {
		if ( 'settings_page_klaro-cmp' !== $hook_suffix ) {
			return;
		}
		wp_register_style( 'klaro-cmp-admin-help', false, array(), KLARO_CMP_VERSION );
		wp_enqueue_style( 'klaro-cmp-admin-help' );
		wp_add_inline_style(
			'klaro-cmp-admin-help',
			'.klaro-cmp-help-row{margin-top:6px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
			.klaro-cmp-help-toggle{min-width:30px;padding:0 6px!important;line-height:1;height:28px;}
			.klaro-cmp-help-toggle .dashicons{font-size:16px;width:16px;height:16px;line-height:1.6;}
			.klaro-cmp-help-panel{margin:10px 0 0;padding:12px 14px;max-width:920px;background:#f6f7f7;border:1px solid #c3c4c7;border-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.04);}
			.klaro-cmp-help-panel[hidden]{display:none!important;}
			.klaro-cmp-help-panel h4{margin:1em 0 .5em;font-size:13px;}
			.klaro-cmp-help-panel h4:first-child{margin-top:0;}
			.klaro-cmp-help-panel pre{white-space:pre-wrap;word-break:break-word;background:#fff;border:1px solid #dcdcde;padding:10px 12px;overflow:auto;max-height:320px;}
			.klaro-cmp-help-panel ul{margin:.4em 0 .8em 1.2em;list-style:disc;}
			.klaro-cmp-help-panel code{font-size:12px;}'
		);
		wp_register_script( 'klaro-cmp-admin-help', false, array(), KLARO_CMP_VERSION, true );
		wp_enqueue_script( 'klaro-cmp-admin-help' );
		wp_add_inline_script(
			'klaro-cmp-admin-help',
			'document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".klaro-cmp-help-toggle").forEach(function(b){b.addEventListener("click",function(){var id=this.getAttribute("aria-controls"),p=id?document.getElementById(id):null;if(!p)return;var o=this.getAttribute("aria-expanded")==="true";this.setAttribute("aria-expanded",o?"false":"true");p.hidden=o;});});});'
		);
	}

	public function admin_notices() {
		if ( ! current_user_can( 'manage_options' ) || ! defined( 'KLARO_CMP_PATH' ) ) {
			return;
		}
		$o = get_option( self::OPTION, Klaro_CMP_Config::defaults() );
		if ( ! is_array( $o ) || empty( $o['enabled'] ) || ( isset( $o['script_source'] ) && 'cdn' === $o['script_source'] ) ) {
			return;
		}
		$need = ( isset( $o['bundle'] ) && 'no_css' === $o['bundle'] )
			? KLARO_CMP_PATH . 'assets/vendor/klaro-no-css.js'
			: KLARO_CMP_PATH . 'assets/vendor/klaro.js';
		if ( is_readable( $need ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'Klaro CMP is enabled but bundled JavaScript files are missing from the plugin. Reinstall the plugin or switch to the CDN under Settings → Klaro.', 'klaro-cmp' );
		echo '</p></div>';
	}

	public function menu() {
		add_options_page(
			__( 'Klaro CMP', 'klaro-cmp' ),
			__( 'Klaro', 'klaro-cmp' ),
			'manage_options',
			'klaro-cmp',
			array( $this, 'render_page' )
		);
	}

	public function register() {
		register_setting(
			'klaro_cmp',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => Klaro_CMP_Config::defaults(),
			)
		);

		add_settings_section(
			'klaro_cmp_general',
			__( 'General', 'klaro-cmp' ),
			'__return_false',
			'klaro-cmp'
		);

		$fields = array(
			'enabled'             => __( 'Enable Klaro', 'klaro-cmp' ),
			'script_source'       => __( 'Script source', 'klaro-cmp' ),
			'bundle'              => __( 'Bundled build', 'klaro-cmp' ),
			'cdn_version'         => __( 'CDN version tag', 'klaro-cmp' ),
			'hide_for_admins'     => __( 'Hide banner for administrators', 'klaro-cmp' ),
			'privacy_policy_url'  => __( 'Privacy policy URL or path', 'klaro-cmp' ),
			'lang'                => __( 'Language code (optional)', 'klaro-cmp' ),
			'styling_theme'       => __( 'UI theme tokens', 'klaro-cmp' ),
			'services_json'       => __( 'Services (JSON array)', 'klaro-cmp' ),
			'translations_json'   => __( 'Extra translations (JSON object)', 'klaro-cmp' ),
		);

		add_settings_field( 'enabled', $fields['enabled'], array( $this, 'field_enabled' ), 'klaro-cmp', 'klaro_cmp_general' );
		add_settings_field( 'script_source', $fields['script_source'], array( $this, 'field_script_source' ), 'klaro-cmp', 'klaro_cmp_general' );
		add_settings_field( 'bundle', $fields['bundle'], array( $this, 'field_bundle' ), 'klaro-cmp', 'klaro_cmp_general' );
		add_settings_field( 'cdn_version', $fields['cdn_version'], array( $this, 'field_cdn_version' ), 'klaro-cmp', 'klaro_cmp_general' );
		add_settings_field( 'hide_for_admins', $fields['hide_for_admins'], array( $this, 'field_hide_for_admins' ), 'klaro-cmp', 'klaro_cmp_general' );

		add_settings_section(
			'klaro_cmp_behavior',
			__( 'Behavior', 'klaro-cmp' ),
			array( $this, 'section_behavior_intro' ),
			'klaro-cmp'
		);

		add_settings_field( 'must_consent', __( 'Must consent (blocking modal)', 'klaro-cmp' ), array( $this, 'field_must_consent' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'no_auto_load', __( 'Do not auto-load Klaro on page load', 'klaro-cmp' ), array( $this, 'field_no_auto_load' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'accept_all', __( 'Show “accept all” (not only OK)', 'klaro-cmp' ), array( $this, 'field_accept_all' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'hide_decline_all', __( 'Hide “decline all”', 'klaro-cmp' ), array( $this, 'field_hide_decline_all' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'hide_learn_more', __( 'Hide “learn more” link', 'klaro-cmp' ), array( $this, 'field_hide_learn_more' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'notice_as_modal', __( 'Show notice as modal', 'klaro-cmp' ), array( $this, 'field_notice_as_modal' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'group_by_purpose', __( 'Group services by purpose', 'klaro-cmp' ), array( $this, 'field_group_by_purpose' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'html_texts', __( 'Allow HTML in consent texts', 'klaro-cmp' ), array( $this, 'field_html_texts' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'embedded', __( 'Embedded mode (no full-screen overlay)', 'klaro-cmp' ), array( $this, 'field_embedded' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'default_enabled', __( 'Optional services on by default (opt-out)', 'klaro-cmp' ), array( $this, 'field_default_enabled' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'show_description_empty_store', __( 'Show description when contextual store is empty', 'klaro-cmp' ), array( $this, 'field_show_description_empty_store' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'auto_focus', __( 'Autofocus consent notice', 'klaro-cmp' ), array( $this, 'field_auto_focus' ), 'klaro-cmp', 'klaro_cmp_behavior' );
		add_settings_field( 'show_notice_title', __( 'Show title on consent notice', 'klaro-cmp' ), array( $this, 'field_show_notice_title' ), 'klaro-cmp', 'klaro_cmp_behavior' );

		add_settings_section(
			'klaro_cmp_storage',
			__( 'Storage', 'klaro-cmp' ),
			'__return_false',
			'klaro-cmp'
		);

		add_settings_field( 'storage_method', __( 'Storage method', 'klaro-cmp' ), array( $this, 'field_storage_method' ), 'klaro-cmp', 'klaro_cmp_storage' );
		add_settings_field( 'cookie_name', __( 'Cookie / storage key name', 'klaro-cmp' ), array( $this, 'field_cookie_name' ), 'klaro-cmp', 'klaro_cmp_storage' );
		add_settings_field( 'cookie_expires_days', __( 'Cookie expiration (days)', 'klaro-cmp' ), array( $this, 'field_cookie_expires_days' ), 'klaro-cmp', 'klaro_cmp_storage' );

		add_settings_section(
			'klaro_cmp_content',
			__( 'Content & configuration', 'klaro-cmp' ),
			array( $this, 'section_content_intro' ),
			'klaro-cmp'
		);

		add_settings_field( 'privacy_policy_url', $fields['privacy_policy_url'], array( $this, 'field_privacy_policy_url' ), 'klaro-cmp', 'klaro_cmp_content' );

		add_settings_section(
			'klaro_cmp_notice_copy',
			__( 'Consent notice text', 'klaro-cmp' ),
			array( $this, 'section_notice_copy_intro' ),
			'klaro-cmp'
		);
		add_settings_field( 'consent_notice_title', __( 'Notice title', 'klaro-cmp' ), array( $this, 'field_consent_notice_title' ), 'klaro-cmp', 'klaro_cmp_notice_copy' );
		add_settings_field( 'consent_notice_description', __( 'Notice description', 'klaro-cmp' ), array( $this, 'field_consent_notice_description' ), 'klaro-cmp', 'klaro_cmp_notice_copy' );
		add_settings_field( 'consent_notice_learn_more', __( '“Learn more” / open choices', 'klaro-cmp' ), array( $this, 'field_consent_notice_learn_more' ), 'klaro-cmp', 'klaro_cmp_notice_copy' );
		add_settings_field( 'consent_notice_decline', __( 'Decline button', 'klaro-cmp' ), array( $this, 'field_consent_notice_decline' ), 'klaro-cmp', 'klaro_cmp_notice_copy' );
		add_settings_field( 'consent_notice_ok', __( 'Accept button (OK)', 'klaro-cmp' ), array( $this, 'field_consent_notice_ok' ), 'klaro-cmp', 'klaro_cmp_notice_copy' );

		add_settings_field( 'lang', $fields['lang'], array( $this, 'field_lang' ), 'klaro-cmp', 'klaro_cmp_content' );
		add_settings_field( 'styling_theme', $fields['styling_theme'], array( $this, 'field_styling_theme' ), 'klaro-cmp', 'klaro_cmp_content' );
		add_settings_field( 'element_id', __( 'DOM element ID for Klaro root', 'klaro-cmp' ), array( $this, 'field_element_id' ), 'klaro-cmp', 'klaro_cmp_content' );
		add_settings_field( 'additional_class', __( 'Additional CSS class on Klaro root', 'klaro-cmp' ), array( $this, 'field_additional_class' ), 'klaro-cmp', 'klaro_cmp_content' );
		add_settings_field( 'services_json', $fields['services_json'], array( $this, 'field_services_json' ), 'klaro-cmp', 'klaro_cmp_content' );
		add_settings_field( 'translations_json', $fields['translations_json'], array( $this, 'field_translations_json' ), 'klaro-cmp', 'klaro_cmp_content' );
		add_settings_field( 'disable_powered_by', __( 'Hide “Realized with Klaro” link', 'klaro-cmp' ), array( $this, 'field_disable_powered_by' ), 'klaro-cmp', 'klaro_cmp_content' );
	}

	public function section_behavior_intro() {
		echo '<p>' . esc_html__( 'These options map to Klaro’s config flags. Unchecked means “false” unless noted.', 'klaro-cmp' ) . '</p>';
	}

	public function section_content_intro() {
		echo '<p>' . esc_html__( 'Define third-party services as a JSON array. See the Klaro config documentation and dist/config.js in the Klaro repository. Callback functions cannot be defined in JSON—use custom JavaScript on your site if you need them.', 'klaro-cmp' ) . '</p>';
	}

	public function section_notice_copy_intro() {
		echo '<p>' . esc_html__( 'These strings are merged into Klaro’s translations for zz, en, and your configured language (if any) so they override the bundled English on the front end. Leave a field empty to use the plugin’s default copy. If the same keys appear in “Extra translations JSON”, the JSON wins.', 'klaro-cmp' ) . '</p>';
	}

	/**
	 * @param array<string, mixed>|null $input Raw.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$defaults = Klaro_CMP_Config::defaults();
		$prev     = get_option( self::OPTION, $defaults );
		if ( ! is_array( $prev ) ) {
			$prev = $defaults;
		}
		$input = is_array( $input ) ? $input : array();

		$out = $defaults;

		$out['enabled']                      = ! empty( $input['enabled'] );
		$out['hide_for_admins']              = ! empty( $input['hide_for_admins'] );
		$out['must_consent']                 = ! empty( $input['must_consent'] );
		$out['no_auto_load']                 = ! empty( $input['no_auto_load'] );
		$out['accept_all']                   = ! empty( $input['accept_all'] );
		$out['hide_decline_all']             = ! empty( $input['hide_decline_all'] );
		$out['hide_learn_more']              = ! empty( $input['hide_learn_more'] );
		$out['notice_as_modal']              = ! empty( $input['notice_as_modal'] );
		$out['group_by_purpose']             = ! empty( $input['group_by_purpose'] );
		$out['html_texts']                   = ! empty( $input['html_texts'] );
		$out['embedded']                     = ! empty( $input['embedded'] );
		$out['default_enabled']              = ! empty( $input['default_enabled'] );
		$out['show_description_empty_store'] = ! empty( $input['show_description_empty_store'] );
		$out['auto_focus']                   = ! empty( $input['auto_focus'] );
		$out['show_notice_title']            = ! empty( $input['show_notice_title'] );
		$out['disable_powered_by']           = ! empty( $input['disable_powered_by'] );

		$src = isset( $input['script_source'] ) ? (string) $input['script_source'] : 'bundled';
		$out['script_source'] = in_array( $src, array( 'bundled', 'cdn' ), true ) ? $src : 'bundled';

		$bundle = isset( $input['bundle'] ) ? (string) $input['bundle'] : 'full';
		$out['bundle'] = in_array( $bundle, array( 'full', 'no_css' ), true ) ? $bundle : 'full';

		$cdn = isset( $input['cdn_version'] ) ? trim( wp_unslash( (string) $input['cdn_version'] ) ) : '';
		if ( $cdn === '' ) {
			$cdn = $defaults['cdn_version'];
		}
		if ( ! preg_match( '/^v?\d+\.\d+\.\d+$/', $cdn ) ) {
			add_settings_error( 'klaro_cmp', 'cdn_version', __( 'CDN version must look like v0.7.22 or 0.7.22. Previous value kept.', 'klaro-cmp' ) );
			$out['cdn_version'] = $prev['cdn_version'] ?? $defaults['cdn_version'];
		} else {
			if ( strpos( $cdn, 'v' ) !== 0 ) {
				$cdn = 'v' . $cdn;
			}
			$out['cdn_version'] = $cdn;
		}

		$out['privacy_policy_url'] = isset( $input['privacy_policy_url'] ) ? sanitize_text_field( wp_unslash( (string) $input['privacy_policy_url'] ) ) : '';
		$out['consent_notice_title'] = isset( $input['consent_notice_title'] ) ? sanitize_text_field( wp_unslash( (string) $input['consent_notice_title'] ) ) : '';
		$out['consent_notice_description'] = isset( $input['consent_notice_description'] ) ? sanitize_textarea_field( wp_unslash( (string) $input['consent_notice_description'] ) ) : '';
		$out['consent_notice_learn_more'] = isset( $input['consent_notice_learn_more'] ) ? sanitize_text_field( wp_unslash( (string) $input['consent_notice_learn_more'] ) ) : '';
		$out['consent_notice_decline'] = isset( $input['consent_notice_decline'] ) ? sanitize_text_field( wp_unslash( (string) $input['consent_notice_decline'] ) ) : '';
		$out['consent_notice_ok'] = isset( $input['consent_notice_ok'] ) ? sanitize_text_field( wp_unslash( (string) $input['consent_notice_ok'] ) ) : '';
		$out['lang']               = isset( $input['lang'] ) ? sanitize_text_field( wp_unslash( (string) $input['lang'] ) ) : '';
		$out['styling_theme']      = isset( $input['styling_theme'] ) ? sanitize_text_field( wp_unslash( (string) $input['styling_theme'] ) ) : $defaults['styling_theme'];
		$out['element_id']         = isset( $input['element_id'] ) ? sanitize_text_field( wp_unslash( (string) $input['element_id'] ) ) : 'klaro';
		$out['additional_class']   = isset( $input['additional_class'] ) ? sanitize_text_field( wp_unslash( (string) $input['additional_class'] ) ) : '';

		$stor = isset( $input['storage_method'] ) ? (string) $input['storage_method'] : 'cookie';
		$out['storage_method'] = in_array( $stor, array( 'cookie', 'localStorage' ), true ) ? $stor : 'cookie';

		$out['cookie_name'] = isset( $input['cookie_name'] ) ? sanitize_text_field( wp_unslash( (string) $input['cookie_name'] ) ) : 'klaro';
		if ( $out['cookie_name'] === '' ) {
			$out['cookie_name'] = 'klaro';
		}

		$days = isset( $input['cookie_expires_days'] ) ? (int) $input['cookie_expires_days'] : 365;
		$out['cookie_expires_days'] = max( 1, min( 3650, $days ) );

		$services_raw = isset( $input['services_json'] ) ? wp_unslash( (string) $input['services_json'] ) : '[]';
		json_decode( $services_raw );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			add_settings_error( 'klaro_cmp', 'services_json', __( 'Services JSON is invalid. Previous value kept.', 'klaro-cmp' ) );
			$out['services_json'] = $prev['services_json'] ?? '[]';
		} else {
			$out['services_json'] = $services_raw;
		}

		$trans_raw = isset( $input['additional_translations_json'] ) ? wp_unslash( (string) $input['additional_translations_json'] ) : '{}';
		$trans_dec = json_decode( $trans_raw, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $trans_dec ) ) {
			add_settings_error( 'klaro_cmp', 'translations_json', __( 'Translations JSON must be a JSON object. Previous value kept.', 'klaro-cmp' ) );
			$out['additional_translations_json'] = $prev['additional_translations_json'] ?? '{}';
		} else {
			$out['additional_translations_json'] = wp_json_encode( $trans_dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}

		return $out;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get() {
		$o = get_option( self::OPTION, Klaro_CMP_Config::defaults() );
		$o = is_array( $o ) ? array_merge( Klaro_CMP_Config::defaults(), $o ) : Klaro_CMP_Config::defaults();
		return Klaro_CMP_Config::with_notice_defaults( $o );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap">';
		settings_errors( 'klaro_cmp' );
		echo '<h1>' . esc_html__( 'Klaro consent manager', 'klaro-cmp' ) . '</h1>';
		echo '<p>' . esc_html__( 'Klaro loads on the public site when enabled. Tag third-party scripts with data-name / data-src as described in the Klaro documentation.', 'klaro-cmp' ) . '</p>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'klaro_cmp' );
		do_settings_sections( 'klaro-cmp' );
		submit_button();
		echo '</form></div>';
	}

	private function field_checkbox( $key, $label ) {
		$o = $this->get();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $o[ $key ] ) ); ?> />
			<?php echo esc_html( $label ); ?>
		</label>
		<?php
	}

	public function field_enabled() {
		$this->field_checkbox( 'enabled', __( 'Load Klaro on the front end', 'klaro-cmp' ) );
	}

	public function field_hide_for_admins() {
		$this->field_checkbox(
			'hide_for_admins',
			__( 'When checked, anyone who can manage options will not see Klaro or its scripts—use a private window or another browser to test. Leave unchecked if you expect to see the banner while logged in.', 'klaro-cmp' )
		);
	}

	public function field_must_consent() {
		$this->field_checkbox( 'must_consent', '' );
	}

	public function field_no_auto_load() {
		$this->field_checkbox( 'no_auto_load', '' );
	}

	public function field_accept_all() {
		$this->field_checkbox( 'accept_all', '' );
	}

	public function field_hide_decline_all() {
		$this->field_checkbox( 'hide_decline_all', '' );
	}

	public function field_hide_learn_more() {
		$this->field_checkbox( 'hide_learn_more', '' );
	}

	public function field_notice_as_modal() {
		$this->field_checkbox( 'notice_as_modal', '' );
	}

	public function field_group_by_purpose() {
		$this->field_checkbox( 'group_by_purpose', '' );
	}

	public function field_html_texts() {
		$this->field_checkbox( 'html_texts', '' );
	}

	public function field_embedded() {
		$this->field_checkbox( 'embedded', '' );
	}

	public function field_default_enabled() {
		$this->field_checkbox( 'default_enabled', '' );
		echo '<p class="description">' . esc_html__( 'When enabled, toggles in the “Learn more” modal start on; visitors opt out by turning them off. Per-service "default": false in the Services JSON keeps that service off unless you omit the key.', 'klaro-cmp' ) . '</p>';
	}

	public function field_show_description_empty_store() {
		$this->field_checkbox( 'show_description_empty_store', '' );
	}

	public function field_auto_focus() {
		$this->field_checkbox( 'auto_focus', '' );
	}

	public function field_show_notice_title() {
		$this->field_checkbox( 'show_notice_title', '' );
	}

	public function field_disable_powered_by() {
		$this->field_checkbox( 'disable_powered_by', __( 'Only disable if your legal or design guidelines require it; Klaro is BSD-licensed and free.', 'klaro-cmp' ) );
	}

	public function field_script_source() {
		$o = $this->get();
		?>
		<fieldset>
			<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[script_source]" value="bundled" <?php checked( $o['script_source'], 'bundled' ); ?> /> <?php esc_html_e( 'Bundled (files in this plugin’s assets folder)', 'klaro-cmp' ); ?></label><br />
			<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[script_source]" value="cdn" <?php checked( $o['script_source'], 'cdn' ); ?> /> <?php esc_html_e( 'CDN (cdn.kiprotect.com)', 'klaro-cmp' ); ?></label>
		</fieldset>
		<?php
	}

	public function field_bundle() {
		$o = $this->get();
		?>
		<fieldset>
			<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[bundle]" value="full" <?php checked( $o['bundle'], 'full' ); ?> /> <?php esc_html_e( 'Full build (JavaScript includes styles)', 'klaro-cmp' ); ?></label><br />
			<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[bundle]" value="no_css" <?php checked( $o['bundle'], 'no_css' ); ?> /> <?php esc_html_e( 'No-CSS build + klaro.min.css (bundled only)', 'klaro-cmp' ); ?></label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'CDN always uses the full build with embedded CSS.', 'klaro-cmp' ); ?></p>
		<?php
	}

	public function field_cdn_version() {
		$o = $this->get();
		?>
		<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[cdn_version]" value="<?php echo esc_attr( $o['cdn_version'] ); ?>" />
		<p class="description"><?php esc_html_e( 'Example: v0.7.22. Pin a specific release; avoid unpinned “latest” URLs.', 'klaro-cmp' ); ?></p>
		<?php
	}

	public function field_storage_method() {
		$o = $this->get();
		?>
		<select name="<?php echo esc_attr( self::OPTION ); ?>[storage_method]">
			<option value="cookie" <?php selected( $o['storage_method'], 'cookie' ); ?>><?php esc_html_e( 'Cookie', 'klaro-cmp' ); ?></option>
			<option value="localStorage" <?php selected( $o['storage_method'], 'localStorage' ); ?>><?php esc_html_e( 'localStorage', 'klaro-cmp' ); ?></option>
		</select>
		<?php
	}

	public function field_cookie_name() {
		$o = $this->get();
		?>
		<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[cookie_name]" value="<?php echo esc_attr( $o['cookie_name'] ); ?>" />
		<?php
	}

	public function field_cookie_expires_days() {
		$o = $this->get();
		?>
		<input type="number" min="1" max="3650" class="small-text" name="<?php echo esc_attr( self::OPTION ); ?>[cookie_expires_days]" value="<?php echo esc_attr( (string) $o['cookie_expires_days'] ); ?>" />
		<?php
	}

	public function field_privacy_policy_url() {
		$o = $this->get();
		?>
		<input type="text" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[privacy_policy_url]" value="<?php echo esc_attr( $o['privacy_policy_url'] ); ?>" placeholder="/privacy-policy/" />
		<?php
	}

	public function field_consent_notice_title() {
		$o = $this->get();
		?>
		<input type="text" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[consent_notice_title]" value="<?php echo esc_attr( $o['consent_notice_title'] ); ?>" placeholder="<?php echo esc_attr__( 'Cookie Consent', 'klaro-cmp' ); ?>" />
		<p class="description"><?php esc_html_e( 'Shown only if “Show title on consent notice” is enabled under Behavior.', 'klaro-cmp' ); ?></p>
		<?php
	}

	public function field_consent_notice_description() {
		$o = $this->get();
		$placeholder = __( 'Hi! Could we please enable some additional services for {purposes}? You can always change or withdraw your consent later.', 'klaro-cmp' );
		?>
		<textarea class="large-text" rows="4" name="<?php echo esc_attr( self::OPTION ); ?>[consent_notice_description]" placeholder="<?php echo esc_attr( $placeholder ); ?>"><?php echo esc_textarea( $o['consent_notice_description'] ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Use the placeholder {purposes} to insert the list of purpose names (e.g. Analytics & Marketing). Turn on “Allow HTML in consent texts” if you need basic HTML.', 'klaro-cmp' ); ?></p>
		<?php
	}

	public function field_consent_notice_learn_more() {
		$o = $this->get();
		?>
		<input type="text" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[consent_notice_learn_more]" value="<?php echo esc_attr( $o['consent_notice_learn_more'] ); ?>" placeholder="<?php echo esc_attr__( 'Let me choose', 'klaro-cmp' ); ?>" />
		<p class="description"><?php esc_html_e( 'Label for the control that opens the full consent manager (hidden if “Hide learn more” is on).', 'klaro-cmp' ); ?></p>
		<?php
	}

	public function field_consent_notice_decline() {
		$o = $this->get();
		?>
		<input type="text" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[consent_notice_decline]" value="<?php echo esc_attr( $o['consent_notice_decline'] ); ?>" placeholder="<?php echo esc_attr__( 'I decline', 'klaro-cmp' ); ?>" />
		<?php
	}

	public function field_consent_notice_ok() {
		$o = $this->get();
		?>
		<input type="text" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[consent_notice_ok]" value="<?php echo esc_attr( $o['consent_notice_ok'] ); ?>" placeholder="<?php echo esc_attr__( 'That\'s ok', 'klaro-cmp' ); ?>" />
		<?php
	}

	public function field_lang() {
		$o = $this->get();
		?>
		<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[lang]" value="<?php echo esc_attr( $o['lang'] ); ?>" placeholder="en" />
		<p class="description"><?php esc_html_e( 'Leave empty to let Klaro follow the document language.', 'klaro-cmp' ); ?></p>
		<?php
	}

	public function field_styling_theme() {
		$o = $this->get();
		?>
		<input type="text" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[styling_theme]" value="<?php echo esc_attr( $o['styling_theme'] ); ?>" />
		<p class="description">
			<?php
			esc_html_e( 'Comma-separated Klaro theme tokens. Position: use bottom or top (not both), and left, right, or wide (wide is a full-width bar; do not combine wide with right). Example for bottom-right: light,bottom,right. Example for top full-width bar: light,top,wide.', 'klaro-cmp' );
			?>
		</p>
		<?php
	}

	public function field_element_id() {
		$o = $this->get();
		?>
		<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[element_id]" value="<?php echo esc_attr( $o['element_id'] ); ?>" />
		<?php
	}

	public function field_additional_class() {
		$o = $this->get();
		?>
		<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[additional_class]" value="<?php echo esc_attr( $o['additional_class'] ); ?>" />
		<?php
	}

	public function field_services_json() {
		$o = $this->get();
		$pid = 'klaro-cmp-help-services';
		?>
		<textarea name="<?php echo esc_attr( self::OPTION ); ?>[services_json]" id="klaro-cmp-services-json" rows="14" class="large-text code"><?php echo esc_textarea( $o['services_json'] ); ?></textarea>
		<p class="klaro-cmp-help-row">
			<button type="button" class="button klaro-cmp-help-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $pid ); ?>" title="<?php esc_attr_e( 'Show or hide help', 'klaro-cmp' ); ?>" aria-label="<?php esc_attr_e( 'Toggle help for services JSON', 'klaro-cmp' ); ?>">
				<span class="dashicons dashicons-editor-help" aria-hidden="true"></span>
			</button>
			<span class="description"><?php esc_html_e( 'Help, example, and service field reference', 'klaro-cmp' ); ?></span>
		</p>
		<div class="klaro-cmp-help-panel" id="<?php echo esc_attr( $pid ); ?>" hidden>
			<h4><?php esc_html_e( 'Example (copy and adapt)', 'klaro-cmp' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Each service needs a unique name that matches the data-name attribute on scripts, images, or other elements Klaro should gate.', 'klaro-cmp' ); ?></p>
			<pre><code>[
  {
    "name": "google-analytics",
    "title": "Google Analytics",
    "default": false,
    "purposes": ["analytics"],
    "cookies": ["_ga", "_gid", "_gat"]
  },
  {
    "name": "youtube",
    "title": "YouTube embeds",
    "default": false,
    "purposes": ["marketing"],
    "required": false,
    "optOut": false
  }
]</code></pre>
			<h4><?php esc_html_e( 'Service object fields (JSON)', 'klaro-cmp' ); ?></h4>
			<ul>
				<li><code>name</code> — <?php esc_html_e( 'Required. Short unique id; must match data-name on your tagged markup.', 'klaro-cmp' ); ?></li>
				<li><code>title</code> — <?php esc_html_e( 'Optional. Display name; can be omitted if you define a title in translations.', 'klaro-cmp' ); ?></li>
				<li><code>default</code> — <?php esc_html_e( 'Boolean. If true, pre-enabled until the user changes it (overrides global default for this service).', 'klaro-cmp' ); ?></li>
				<li><code>purposes</code> — <?php esc_html_e( 'Array of purpose ids (e.g. analytics, marketing, security). Define labels under translations → purposes.', 'klaro-cmp' ); ?></li>
				<li><code>cookies</code> — <?php esc_html_e( 'Strings (cookie names), or for regex/path/domain: a three-item array ["^pattern$", "/", "your-domain.com"].', 'klaro-cmp' ); ?></li>
				<li><code>required</code> — <?php esc_html_e( 'If true, user cannot disable this service.', 'klaro-cmp' ); ?></li>
				<li><code>optOut</code> — <?php esc_html_e( 'If true, loads before explicit consent (usually keep false).', 'klaro-cmp' ); ?></li>
				<li><code>onlyOnce</code> — <?php esc_html_e( 'If true, script runs once even if toggled repeatedly.', 'klaro-cmp' ); ?></li>
				<li><code>contextualConsentOnly</code> — <?php esc_html_e( 'Use contextual consent UI only for this service.', 'klaro-cmp' ); ?></li>
			</ul>
			<p class="description"><?php esc_html_e( 'Note: callback functions cannot be stored in JSON. Add them in a separate JavaScript file if you need consent callbacks.', 'klaro-cmp' ); ?></p>
			<p class="description">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: URL to Klaro docs */
						__( 'Full annotated reference: see the Klaro repository <code>dist/config.js</code> and <a href="%s" target="_blank" rel="noopener noreferrer">klaro.kiprotect.com</a>.', 'klaro-cmp' ),
						'https://klaro.kiprotect.com/'
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	public function field_translations_json() {
		$o = $this->get();
		$pid = 'klaro-cmp-help-translations';
		?>
		<textarea name="<?php echo esc_attr( self::OPTION ); ?>[additional_translations_json]" id="klaro-cmp-translations-json" rows="10" class="large-text code"><?php echo esc_textarea( $o['additional_translations_json'] ); ?></textarea>
		<p class="klaro-cmp-help-row">
			<button type="button" class="button klaro-cmp-help-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $pid ); ?>" title="<?php esc_attr_e( 'Show or hide help', 'klaro-cmp' ); ?>" aria-label="<?php esc_attr_e( 'Toggle help for translations JSON', 'klaro-cmp' ); ?>">
				<span class="dashicons dashicons-editor-help" aria-hidden="true"></span>
			</button>
			<span class="description"><?php esc_html_e( 'Help, example, and translation keys', 'klaro-cmp' ); ?></span>
		</p>
		<div class="klaro-cmp-help-panel" id="<?php echo esc_attr( $pid ); ?>" hidden>
			<h4><?php esc_html_e( 'Example (copy and adapt)', 'klaro-cmp' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Keys are language codes (en, de, …). Use zz for defaults that apply when a string is missing. This object is merged with the Privacy policy URL field, the “Consent notice text” fields above, and Klaro’s built-in strings (this JSON overrides those fields when the same keys are present).', 'klaro-cmp' ); ?></p>
			<pre><code>{
  "zz": {
    "privacyPolicyUrl": "/privacy-policy/"
  },
  "en": {
    "consentModal": {
      "title": "Privacy preferences",
      "description": "Here you can review and adjust optional services."
    },
    "google-analytics": {
      "title": "Google Analytics",
      "description": "Helps us understand traffic and improve the site."
    },
    "purposes": {
      "analytics": "Analytics",
      "marketing": "Marketing",
      "security": "Security",
      "livechat": "Live chat",
      "advertising": "Advertising",
      "styling": "Styling"
    }
  }
}</code></pre>
			<h4><?php esc_html_e( 'Common keys (per language)', 'klaro-cmp' ); ?></h4>
			<ul>
				<li><code>privacyPolicyUrl</code> — <?php esc_html_e( 'Link or path to your privacy policy (often under zz or each language).', 'klaro-cmp' ); ?></li>
				<li><code>consentModal.title</code> / <code>consentModal.description</code> — <?php esc_html_e( 'Main modal copy (htmlTexts in settings must be true for HTML).', 'klaro-cmp' ); ?></li>
				<li><code>consentNotice.*</code> — <?php esc_html_e( 'Optional overrides for the first notice (see Klaro defaults in dist/config.js).', 'klaro-cmp' ); ?></li>
				<li><code>&lt;service name&gt;.title</code> / <code>&lt;service name&gt;.description</code> — <?php esc_html_e( 'Strings for each entry in your services array.', 'klaro-cmp' ); ?></li>
				<li><code>purposes.&lt;purpose id&gt;</code> — <?php esc_html_e( 'Human-readable labels for purpose ids used in services.', 'klaro-cmp' ); ?></li>
				<li><code>ok</code>, <code>acceptAll</code>, <code>acceptSelected</code>, <code>decline</code>, <code>close</code>, <code>save</code>, <code>poweredBy</code> — <?php esc_html_e( 'Optional button and footer labels.', 'klaro-cmp' ); ?></li>
			</ul>
			<p class="description"><?php esc_html_e( 'Bundled locale files in the Klaro package (src/translations/*.yml) list everything you can override.', 'klaro-cmp' ); ?></p>
			<p class="description">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: GitHub tree URL */
						__( 'Browse keys: <a href="%s" target="_blank" rel="noopener noreferrer">Klaro translations on GitHub</a>.', 'klaro-cmp' ),
						'https://github.com/kiprotect/klaro/tree/main/src/translations'
					)
				);
				?>
			</p>
		</div>
		<?php
	}
}
