<?php
/**
 * Plugin Name: Multilanguage BuddyPress with Polylang
 * Plugin URI:  http://awesome.ug
 * Description: Getting BuddyPress and polylang together. Early first trying out version.
 * Author:      Awsome UG
 * Version:     0.1.0
 * Author URI:  http://awesome.ug
 */

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BuddyPress_Polylang
 *
 * This class contains basic functionality for getting BuddyPress and Polylang together
 */
class Multilanguage_BP_Polylang {
	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @var BuddyPress_Polylang $instance;
	 */
	protected static $instance;

	/**
	 * BuddyPress_Polylang constructor
	 *
	 * @since 1.0.0
	 */
	final private function __construct() {
		$this->init();
	}

	/**
	 * Getting instance
	 *
	 * @since 1.0.0
	 *
	 * @return BuddyPress_Polylang $instance
	 */
	final public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static;
		}
		return static::$instance;
	}

	/**
	 * Adding Actionhooks & Co.
	 *
	 * @since 1.0.0
	 */
	protected function init() {
		if( ! function_exists( 'buddypress' ) ) {
			// We should output some information fot the user
			// throw new Exception( __( 'BuddyPress is not loaded', 'buddypress-polylang' ), 1 );
		}

		// Including all needed files
		$this->includes();

		// Getting some base information from Polylang (because used later)
		BP_Polylang::get_instance();

		// Loading some base hooks for overwriting locales early enough, replacing Id's and rewriting URL's for BuddyPress
		BP_Translate_Core::get_instance();

		// Translating Emails of BuddyPress
        BP_Translate_Emails::get_instance();
	}

	/**
	 * Polylang Object
	 *
	 * @since 1.0.0
	 *
	 * @return BP_Polylang
	 */
	public function polylang() {
		return BP_Polylang::get_instance();
	}

	/**
	 * Include needed files here
	 */
	private function includes() {
		require_once $this->get_path() . '/class-polylang.php';
		require_once $this->get_path() . '/class-bp-core-translate.php';
		require_once $this->get_path() . '/class-bp-email-translate.php';
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

/**
 * BuddyPress Polylang super function
 *
 * @return Multilanguage_BP_Polylang
 */
function bppl() {
	return Multilanguage_BP_Polylang::get_instance();
}

// Get the shit running! :)
bppl();


