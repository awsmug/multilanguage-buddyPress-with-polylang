<?php

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BuddyPress_Polylang
 *
 * @since 1.0.0
 *
 * This class contains basic functionality for getting BuddyPress and Polylang together
 */
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
		// We do not need the following functionality in the admin
		if( is_admin() ) {
			return;
		}

		// Overwriting BP URL without language slug, becaus it don't understands the URL
		add_filter( 'bp_uri', array( $this, 'kill_language_slug' ), 0 );

		// Overwriting local before polylang des it, beyause Polylang does it too late
		add_filter( 'locale', array( $this, 'overwrite_locale' ) );

		// Redirecting to the current language page Id's
		add_filter( 'bp_core_get_directory_page_ids', array( $this, 'replace_directory_page_ids' ) );
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
	 * @since 1.0.0
	 *
	 * @param $locale
	 *
	 * @return bool|string
	 */
	public function overwrite_locale( $locale ) {
		if( null === $locale = $this->detected_locale ) {
			$lang = $_COOKIE[ 'pll_language' ];

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
	 * @param string $url Actual URL Path
	 *
	 * @return string $url Actual URL Path (filtered without language
	 *
	 * @uses  pll_current_language() to get the current language
	 */
	public function kill_language_slug( $request_uri ) {
		$path = str_replace( '/' . pll_current_language() . '/' , '/', $request_uri );
		return $path;
	}
}


