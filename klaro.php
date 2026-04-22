<?php
/**
 * Plugin Name:       Klaro CMP
 * Plugin URI:        https://klaro.kiprotect.com/
 * Description:       Embeds the Klaro consent manager (GDPR-friendly CMP) with a Settings → Klaro configuration page.
 * Version:           0.7.22
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Jess Nunez + Klaro (bundled)
 * License:           BSD-3-Clause
 * License URI:       https://opensource.org/licenses/BSD-3-Clause
 * Text Domain:       klaro-cmp
 *
 * @package Klaro_CMP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KLARO_CMP_VERSION', '0.7.22' );
define( 'KLARO_CMP_FILE', __FILE__ );
define( 'KLARO_CMP_PATH', plugin_dir_path( __FILE__ ) );
define( 'KLARO_CMP_URL', plugin_dir_url( __FILE__ ) );

require_once KLARO_CMP_PATH . 'includes/class-klaro-cmp-config.php';
require_once KLARO_CMP_PATH . 'includes/class-klaro-cmp-settings.php';
require_once KLARO_CMP_PATH . 'includes/class-klaro-cmp-frontend.php';
require_once KLARO_CMP_PATH . 'includes/class-klaro-cmp-plugin.php';

Klaro_CMP_Plugin::instance();
