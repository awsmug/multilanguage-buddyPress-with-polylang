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
class BuddyPress_Polylang {
	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @var BuddyPress_Polylang $instance;
	 */
	protected static $instance;

	/**
	 * Locale
	 *
	 * @var string
	 */
	protected $locale = null;

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
			// throw new Exception( __( 'BuddyPress is not loaded', 'buddypress-polylang' ), 1 );
		}
		if( is_admin() ){
			return;
		}
		add_filter( 'bp_core_get_directory_page_ids', array( $this, 'replace_directory_page_ids' ) );
		add_filter( 'bp_uri', array( $this, 'kill_language_slug' ), 0 );
		add_filter( 'locale', array( $this, 'overwrite_locale' ) );
	}

	/**
	 * Filtering the directory page Ids to related pages of current language
	 *
	 * @since 1.0.0
	 *
	 * @param array $page_ids Page Ids in an array
	 *
	 * @return array $page_ids Page Ids in an array (filtered in correct language)
	 *
	 * @uses pll_current_language() to get the current selected language
	 * @uses pll_get_post() to get the related post in the current language
	 */
	public function replace_directory_page_ids( $page_ids ) {
		$current_language = pll_current_language();

		foreach( $page_ids AS $component_slug => $page_id ) {
			$current_language_post_id = pll_get_post( $page_id, $current_language );

			if( false !== $current_language_post_id ) {
				$page_ids[ $component_slug ] = $current_language_post_id;
			}
		}

		return $page_ids;
	}

	/**
	 * Overwriting Locale
	 *
	 * PolyLang switches language a little late. We are switching in the moment we have the language.
	 *
	 * @param $locale
	 *
	 * @return bool|string
	 */
	public function overwrite_locale( $locale ) {
		if( null === $locale = $this->locale ) {
			$lang = $_COOKIE[ 'pll_language' ];

			if( false === $loaded_locale = $this->get_locale_from_transient( $lang ) ) {
				$loaded_locale = $this->get_locales_from_db( $lang );
			}
			$this->locale = $loaded_locale;
		}

		if( false === $this->locale ) {
			return $locale;
		}

		return $this->locale;
	}

	/**
	 * Get locale from cache
	 *
	 * @param string $lang Language String (de,en...)
	 *
	 * @return bool|string $locale (de_DE,en_EN...)
	 */
	private function get_locale_from_transient( $lang ) {
		$languages = get_transient( 'pll_languages_list' );

		foreach( $languages AS $language ) {
			if( $language[ 'slug' ] === $lang ) {
				return $language[ 'locale' ];
			}
		}
		return false;
	}

	/**
	 * Get locale from DB if transient is not set
	 *
	 * @param string $lang Language String (de,en...)
	 *
	 * @return bool|string $locale (de_DE,en_EN...)
	 */
	private function get_locales_from_db( $lang ){
		global $wpdb;

		$languages = $wpdb->get_col( "SELECT description FROM {$wpdb->term_taxonomy} WHERE taxonomy='language'" );
		foreach ( $languages AS $language ) {
			$language = maybe_unserialize( $language );
			if( $language[ 'flag_code' ] === $lang ) {
				return $language[ 'locale' ];
			}
		}

		return false;
	}

	/**
	 * Removing language slug from BuddyPress URL
	 *
	 * BuddyPress is irritated about language slugs from polylang and can't setup anymore with polylang. So we kill the language slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Actual URL Path
	 *
	 * @return string $path Actual URL Path (filtered without language
	 *
	 * @uses pll_current_language() to get the current selected language
	 */
	public function kill_language_slug( $path ) {
		$current_language = pll_current_language();
		$path = str_replace( '/' . $current_language . '/' , '/', $path );
		return $path;
	}
}

/**
 * BuddyPress Polylang super function
 *
 * @return BuddyPress_Polylang
 */
function bppl() {
	return BuddyPress_Polylang::get_instance();
}

// Get the shit running! :)
bppl();


