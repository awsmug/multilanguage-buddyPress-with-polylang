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

require 'lib/traits/trait-wp-message.php';

/**
 * Class Multilanguage_BP_Polylang
 *
 * This class contains basic functionality for getting BuddyPress and Polylang together
 */
class Multilanguage_BP_Polylang {
    use BPPL_WP_Messages;

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @var Multilanguage_BP_Polylang $instance;
	 */
	protected static $instance = null;

	/**
	 * BuddyPress_Polylang constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Getting instance
	 *
	 * @since 1.0.0
	 *
	 * @return Multilanguage_BP_Polylang $instance
	 */
	public static function get_instance() {
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
	private function init() {
	    $this->messages_init();
	    $this->messages_prefix( __( 'Multilanguage BuddyPress with Polylang: ', 'buddypress-polylang' ) );

		// Including all needed files
		$this->includes();

		add_action( 'plugins_loaded', array( $this, 'do_checks' ) );

		// Getting some base information from Polylang (because used later)
		BP_Polylang::get_instance();

		// Loading some base hooks for overwriting locales early enough, replacing Id's and rewriting URL's for BuddyPress
		BP_Translate_Core::get_instance();

		// Translating Emails of BuddyPress
        BP_Translate_Emails::get_instance();
	}

	public function do_checks() {
        if( ! function_exists( 'buddypress' ) ) {
            // We should output some information fot the user
            $this->message( __( 'BuddyPress is not loaded. Please install and activate BuddyPress.', 'buddypress-polylang' ) );
        }

        if( ! function_exists( 'pll_current_language' ) ) {
            // We should output some information fot the user
            $this->message( __( 'Polylang is not loaded. Please install and activate Polylang.', 'buddypress-polylang' ) );
        }
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
		require_once self::get_path() . '/class-polylang.php';
		require_once self::get_path() . '/class-bp-core-translate.php';
		require_once self::get_path() . '/class-bp-email-translate.php';
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

bppl();

// Get it running! Have to be loaded at Zero, because Polylang is a bit too fast. :)
// add_action( 'plugins_loaded', 'bppl', 1 );


