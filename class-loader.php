<?php
/**
 * Class BPPL_Loader
 *
 * @since 1.0.0
 *
 * This class contains includes all necessary files and checks the WordPress installation for needed settings
 */

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BPPL_Loader {
	/**
	 * Adding Actionhooks & Co.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::includes();
		add_action( 'plugins_loaded', array( __CLASS__, 'do_checks' ) );
	}

	/**
	 * Check if everything is running in WP
	 *
	 * @since 1.0.0
	 */
	public static function do_checks() {
		if ( ! function_exists( 'buddypress' ) ) {
			// We should output some information fot the user
			bppl_messages()->add( __( 'BuddyPress is not loaded. Please install and activate BuddyPress.', 'buddypress-polylang' ) );
		}

		if ( ! function_exists( 'pll_current_language' ) ) {
			// We should output some information fot the user
			bppl_messages()->add( __( 'Polylang is not loaded. Please install and activate Polylang.', 'buddypress-polylang' ) );
		}
	}

	/**
	 * Include needed files here
	 *
	 * @since 1.0.0
	 */
	private static function includes() {
		require_once dirname( __FILE__ ) . '/lib/class-messages.php';
		require_once dirname( __FILE__ ) . '/class-manager.php';
		require_once dirname( __FILE__ ) . '/class-user.php';
		require_once dirname( __FILE__ ) . '/class-polylang.php';
		require_once dirname( __FILE__ ) . '/class-buddypress.php';
		require_once dirname( __FILE__ ) . '/class-buddypress-emails.php';
	}

	/**
	 * Getting Plugin Path
	 *
	 * @since 1.0.0
	 *
	 * @return string $path System path to the plugin directory
	 *
	 * @uses plugin_dir_path() To get path to plugin directory
	 */
	public static function get_path() {
		return plugin_dir_path( __FILE__ );
	}

	/**
	 * Getting Plugin URL
	 *
	 * @since 1.0.0
	 *
	 * @return string $url URL to the plugin directory
	 *
	 * @uses plugin_dir_url() To get url to plugin directory
	 */
	public static function get_url() {
		return plugin_dir_url( __FILE__ );
	}
}


