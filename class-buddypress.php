<?php
/**
 * Class BPPL_BuddyPress
 *
 * @since 1.0.0
 *
 * This class managaes all needed BuddyPress functionality for translations
 */

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BPPL_BuddyPress {
	/**
	 * Detected Locale (from Polylang settings)
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $detected_locale = null;

	/**
	 * BuddyPress_Polylang constructor
	 *
	 * @since 1.0.0
	 */
	final public function __construct() {
		// Overwriting local before polylang des it, beyause Polylang does it too late
		add_filter( 'locale', array( $this, 'overwrite_locale' ) );

		if( is_admin() ) {
			return;
		}

		add_filter( 'bp_uri', array( $this, 'remove_language_slug' ), 0 );
		// Redirecting to the current language page Id's
		add_filter( 'bp_core_get_directory_page_ids', array( $this, 'replace_directory_page_ids' ) );
		// Adding and removing language slug to url
		add_filter( 'bp_core_get_root_domain', array( $this, 'add_language_slug' ) );
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
		if( ! function_exists( 'pll_current_language' ) ) {
			return $page_ids;
		}

		$current_language = pll_current_language();

		if( false === $current_language ) {
			return $page_ids;
		}

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
	 * @since 1.0.0
	 *
	 * @param $locale
	 *
	 * @return bool|string
	 */
	public function overwrite_locale( $locale ) {
		if( null === $locale = $this->detected_locale ) {
			if( false === $lang = $this->get_user_lang() ) {
				return $locale;
			}

			if( false === $loaded_locale = $this->get_locale_from_transient( $lang ) ) {
				$loaded_locale = $this->get_locales_from_db( $lang );
			}
			$this->detected_locale = $loaded_locale;
		}

		if( false === $this->detected_locale ) {
			return $locale;
		}

		return $this->detected_locale;
	}

	public function get_user_lang() {
		if( array_key_exists( 'pll_language', $_COOKIE ) ) {
			return $_COOKIE[ 'pll_language' ];
		}

		return false;
	}

	/**
	 * Get locale from cache
	 *
	 * @since 1.0.0
	 *
	 * @param string $lang Language String (de,en...)
	 *
	 * @return bool|string $locale (de_DE,en_EN...)
	 */
	private function get_locale_from_transient( $lang ) {
		$languages = get_transient( 'pll_languages_list' );

		if( ! is_array( $languages ) ) {
			return false;
		}

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
	 * @since 1.0.0
	 *
	 * @param string $lang Language String (de,en...)
	 *
	 * @return bool|string $locale (de_DE,en_EN...)
	 */
	private function get_locales_from_db( $lang ){
		global $wpdb;

		$languages = $wpdb->get_col( "SELECT locale FROM {$wpdb->term_taxonomy} WHERE taxonomy='language'" );
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
	 * @param string $request_uri Actual URL Path
	 *
	 * @return string $url Actual URL Path (filtered without language
	 *
	 * @uses  pll_current_language() to get the current language
	 */
	public function remove_language_slug( $request_uri ) {
		if( ! function_exists( 'pll_current_language' ) ) {
			return $request_uri;
		}

		$path = str_replace( '/' . pll_current_language() . '/' , '/', $request_uri );

		return $path;
	}

	/**
	 * Adding slug to BuddyPress root domain
	 *
	 * Needed for links within BuddyPress
	 *
	 * @param string $url Unfiltered BuddyPress root domain.
	 *
	 * @return string $url Filtered BuddyPress root domain.
	 */
	public function add_language_slug( $url ) {
		$locale = get_locale();

		if( false === $locale ) {
			return $url;
		}

		$lang_slug = bppl()->polylang()->get_lang_slug_by_locale( get_locale() );

		if( is_wp_error( $lang_slug ) ) {
			return $url;
		}

		$url = $url . '/' . $lang_slug;

		return $url;
	}
}


