<?php
/**
 * Build klaroConfig array for wp_json_encode (no JS callbacks).
 *
 * @package Klaro_CMP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Klaro_CMP_Config {

	/**
	 * Default consent notice strings (used when settings fields are left empty).
	 *
	 * @return array<string, string>
	 */
	public static function default_notice_strings() {
		return array(
			'consent_notice_title'       => __( 'We value your privacy', 'klaro-cmp' ),
			'consent_notice_description' => __( 'We use cookies to enhance your browsing experience, serve personalized ads or content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.', 'klaro-cmp' ),
			'consent_notice_learn_more'  => __( 'Learn More', 'klaro-cmp' ),
			'consent_notice_decline'    => __( 'Reject All', 'klaro-cmp' ),
			'consent_notice_ok'          => __( 'Accept All', 'klaro-cmp' ),
		);
	}

	/**
	 * Fill empty notice-related strings so the banner always has copy (admin + front end).
	 *
	 * @param array<string, mixed> $s Merged settings.
	 * @return array<string, mixed>
	 */
	public static function with_notice_defaults( array $s ) {
		foreach ( self::default_notice_strings() as $key => $default ) {
			if ( ! isset( $s[ $key ] ) || trim( (string) $s[ $key ] ) === '' ) {
				$s[ $key ] = $default;
			}
		}
		return $s;
	}

	/**
	 * Default option values when the option is missing.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		$notice = self::default_notice_strings();
		return array(
			'enabled'                      => false,
			'script_source'                => 'bundled',
			'bundle'                       => 'full',
			'cdn_version'                  => 'v0.7.22',
			'hide_for_admins'              => false,
			'element_id'                   => 'klaro',
			'styling_theme'                => 'light,bottom,right',
			'show_description_empty_store' => true,
			'no_auto_load'                 => false,
			'html_texts'                   => true,
			'embedded'                     => false,
			'group_by_purpose'             => true,
			'auto_focus'                   => false,
			'show_notice_title'            => true,
			'storage_method'               => 'cookie',
			'cookie_name'                  => 'klaro',
			'cookie_expires_days'          => 365,
			'default_enabled'              => true,
			'must_consent'                 => false,
			'accept_all'                   => true,
			'hide_decline_all'             => false,
			'hide_learn_more'              => false,
			'notice_as_modal'              => false,
			'lang'                         => '',
			'privacy_policy_url'           => '',
			'consent_notice_title'         => $notice['consent_notice_title'],
			'consent_notice_description'   => $notice['consent_notice_description'],
			'consent_notice_learn_more'    => $notice['consent_notice_learn_more'],
			'consent_notice_decline'       => $notice['consent_notice_decline'],
			'consent_notice_ok'            => $notice['consent_notice_ok'],
			'additional_class'             => '',
			'disable_powered_by'           => false,
			'services_json'                => wp_json_encode(
				array(
					array(
						'name'     => 'optional-cookies',
						'title'    => 'Optional cookies',
						'purposes' => array( 'analytics', 'marketing' ),
					),
				),
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			),
			'additional_translations_json' => '{}',
		);
	}

	/**
	 * @param array<string, mixed> $s Sanitized settings.
	 * @return array<string, mixed>
	 */
	public static function build( array $s ) {
		$s = self::with_notice_defaults( $s );

		$theme_parts = array_map( 'trim', explode( ',', (string) ( $s['styling_theme'] ?? 'light,bottom,right' ) ) );
		$theme_parts = array_values( array_filter( $theme_parts ) );
		if ( empty( $theme_parts ) ) {
			$theme_parts = array( 'light', 'bottom', 'right' );
		}

		$translations = array();
		$privacy      = isset( $s['privacy_policy_url'] ) ? trim( (string) $s['privacy_policy_url'] ) : '';
		if ( $privacy !== '' ) {
			$translations['zz'] = array(
				'privacyPolicyUrl' => $privacy,
			);
		}

		/*
		 * Klaro resolves the active locale (usually "en" from <html lang>) before falling back to zz.
		 * Bundled English already defines consentNotice.* / decline / ok, so zz-only overrides never apply.
		 */
		$notice_overrides = self::zz_from_notice_settings( $s );
		if ( ! empty( $notice_overrides ) ) {
			$locales = array( 'zz', 'en' );
			$cfg_lang = isset( $s['lang'] ) ? strtolower( trim( (string) $s['lang'] ) ) : '';
			if ( $cfg_lang !== '' ) {
				$cfg_lang = preg_replace( '/^([a-z]{2,3})(?:-[a-z0-9]+)?$/i', '$1', $cfg_lang );
				if ( ! in_array( $cfg_lang, $locales, true ) ) {
					$locales[] = $cfg_lang;
				}
			}
			foreach ( array_unique( $locales ) as $loc ) {
				if ( ! isset( $translations[ $loc ] ) ) {
					$translations[ $loc ] = array();
				}
				$translations[ $loc ] = array_replace_recursive( $translations[ $loc ], $notice_overrides );
			}
		}

		$extra_t = array();
		if ( ! empty( $s['additional_translations_json'] ) ) {
			$decoded = json_decode( (string) $s['additional_translations_json'], true );
			if ( is_array( $decoded ) ) {
				$extra_t = $decoded;
			}
		}
		if ( ! empty( $extra_t ) ) {
			$translations = array_replace_recursive( $translations, $extra_t );
		}

		$services = array();
		if ( ! empty( $s['services_json'] ) ) {
			$decoded = json_decode( (string) $s['services_json'], true );
			if ( is_array( $decoded ) ) {
				$services = $decoded;
			}
		}
		if ( empty( $services ) ) {
			/**
			 * When the services list is empty, Klaro still renders but the consent modal has no rows.
			 * Default to a minimal opt-in service so Accept / Reject / Learn more behave as expected.
			 *
			 * @param list<array<string, mixed>> $fallback Default services.
			 */
			$services = apply_filters(
				'klaro_cmp_default_services_if_empty',
				array(
					array(
						'name'     => 'optional-cookies',
						'title'    => 'Optional cookies',
						'purposes' => array( 'analytics', 'marketing' ),
					),
				)
			);
		}

		if ( ! empty( $s['default_enabled'] ) && ! empty( $services ) ) {
			foreach ( $services as $idx => $svc ) {
				if ( ! is_array( $svc ) ) {
					continue;
				}
				if ( isset( $svc['default'] ) && false === $svc['default'] && empty( $svc['required'] ) ) {
					unset( $services[ $idx ]['default'] );
				}
			}
		}

		$storage = isset( $s['storage_method'] ) ? (string) $s['storage_method'] : 'cookie';
		if ( ! in_array( $storage, array( 'cookie', 'localStorage' ), true ) ) {
			$storage = 'cookie';
		}

		$cfg = array(
			'version'                   => 1,
			'elementID'                 => ! empty( $s['element_id'] ) ? (string) $s['element_id'] : 'klaro',
			'styling'                   => array(
				'theme' => $theme_parts,
			),
			'showDescriptionEmptyStore' => ! empty( $s['show_description_empty_store'] ),
			'noAutoLoad'                => ! empty( $s['no_auto_load'] ),
			'htmlTexts'                 => ! empty( $s['html_texts'] ),
			'embedded'                  => ! empty( $s['embedded'] ),
			'groupByPurpose'            => ! empty( $s['group_by_purpose'] ),
			'autoFocus'                 => ! empty( $s['auto_focus'] ),
			'showNoticeTitle'           => ! empty( $s['show_notice_title'] ),
			'storageMethod'             => $storage,
			'cookieName'                => ! empty( $s['cookie_name'] ) ? (string) $s['cookie_name'] : 'klaro',
			'cookieExpiresAfterDays'    => isset( $s['cookie_expires_days'] ) ? (int) $s['cookie_expires_days'] : 365,
			'default'                   => ! empty( $s['default_enabled'] ),
			'mustConsent'               => ! empty( $s['must_consent'] ),
			'acceptAll'                 => ! empty( $s['accept_all'] ),
			'hideDeclineAll'            => ! empty( $s['hide_decline_all'] ),
			'hideLearnMore'             => ! empty( $s['hide_learn_more'] ),
			'noticeAsModal'             => ! empty( $s['notice_as_modal'] ),
			'services'                  => $services,
		);

		if ( ! empty( $translations ) ) {
			$cfg['translations'] = $translations;
		}

		$lang = isset( $s['lang'] ) ? trim( (string) $s['lang'] ) : '';
		if ( $lang !== '' ) {
			$cfg['lang'] = $lang;
		}

		$ac = isset( $s['additional_class'] ) ? trim( (string) $s['additional_class'] ) : '';
		if ( $ac !== '' ) {
			$cfg['additionalClass'] = $ac;
		}

		if ( ! empty( $s['disable_powered_by'] ) ) {
			$cfg['disablePoweredBy'] = true;
		}

		return $cfg;
	}

	/**
	 * Map settings fields to Klaro translations.zz (fallback locale).
	 *
	 * @param array<string, mixed> $s Sanitized settings.
	 * @return array<string, mixed>
	 */
	private static function zz_from_notice_settings( array $s ) {
		$zz = array();

		$cn = array(
			'title'       => (string) $s['consent_notice_title'],
			'description' => (string) $s['consent_notice_description'],
			'learnMore'   => (string) $s['consent_notice_learn_more'],
		);
		$zz['consentNotice'] = $cn;
		$zz['decline']       = (string) $s['consent_notice_decline'];
		$zz['ok']            = (string) $s['consent_notice_ok'];
		$zz['acceptAll']     = (string) $s['consent_notice_ok'];

		return $zz;
	}
}
