<?php
/**
 * Remove options when the plugin is deleted.
 *
 * @package Klaro_CMP
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'klaro_cmp_settings' );
