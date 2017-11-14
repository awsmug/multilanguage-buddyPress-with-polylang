<?php
/**
 * Class BPPL_BuddyPress
 *
 * @since 1.0.0
 *
 * This class managaes all needed BuddyPress functionality for translations
 */

if ( ! defined( 'ABSPATH' ) ) {
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
		// Overwriting local before polylang does it, beyause Polylang does it too late
		add_filter( 'locale', array( $this, 'overwrite_locale' ) );

		if ( is_admin() && ! defined('DOING_AJAX') ) {
			return;
		}

		add_filter( 'bp_core_get_directory_page_ids', array( $this, 'replace_directory_page_ids' ) );
		add_filter( 'bp_core_get_directory_pages', array( $this, 'replace_page_slugs' ) );
	}

	/**
	 * Replacing page slugs because BuddyPress uses page slugs to create URLS where language is missed
	 *
	 * @since 1.0.0
	 *
	 * @param stdClass $pages
	 *
	 * @return stdClass $pages
	 */
	public function replace_page_slugs( $pages ) {
		$switched = false;
		if( ! bp_is_root_blog() ) {
			switch_to_blog( bp_get_root_blog_id() );
			$switched = true;
		}

		foreach ( $pages AS $component => $page ) {
			$permalink = get_permalink( $page->id );
			$path = str_replace( bp_core_get_root_domain(), '', $permalink );
			$path = trim ( $path, '/' );
			$page->slug = $path;
			$pages->$component = $page;
		}

		if( $switched ) {
			restore_current_blog();
		}

		return $pages;
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
		if ( ! function_exists( 'pll_current_language' ) ) {
			return $page_ids;
		}

		$current_language = pll_current_language();

		if ( false === $current_language ) {
			return $page_ids;
		}

		foreach ( $page_ids AS $component_slug => $page_id ) {
			$current_language_post_id = pll_get_post( $page_id, $current_language );

			if ( false !== $current_language_post_id ) {
				$page_ids[ $component_slug ] = $current_language_post_id;
			}
		}

		return $page_ids;
	}

	/**
	 * Getting directory page ids ordered by language
	 *
	 * @since 1.0.0
	 *
	 * @return array $directory_page_ids Directory page ids ordered by language
	 */
	public function get_directory_page_ids() {
		// Getting unfiltered page ids
		remove_filter( 'bp_core_get_directory_page_ids', array( $this, 'replace_directory_page_ids' ) );
		$core_page_ids = bp_core_get_directory_page_ids();
		add_filter( 'bp_core_get_directory_page_ids', array( $this, 'replace_directory_page_ids' ) );

		$directory_page_ids = array();
		$languages = bppl()->polylang()->get_languages();

		foreach ( $languages AS $language => $language_info ) {
			$pages_ids = array();

			foreach ( $core_page_ids as $component_slug => $page_id ) {
				$language_post_id = pll_get_post( $page_id, $language );

				$pages_ids[ $component_slug ] = $language_post_id;
			}

			$directory_page_ids[ $language ] = $pages_ids;
		}

		return $directory_page_ids;
	}

	/**
	 * Getting a directory page ID
	 *
	 * @since 1.0.0
	 *
	 * @param string $lang Language slug
	 * @param string $component Name of the BuddyPress component
	 *
	 * @return mixed
	 */
	public function get_directory_page_id( $lang, $component ) {
		$directory_page_ids = $this->get_directory_page_ids();

		$directory_page_id = $directory_page_ids[ $lang ][ $component ];

		return $directory_page_id;
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
	 * @return bool|string $detected_locale Locale which was detected for user
	 */
	public function overwrite_locale( $locale ) {
		if ( null === $this->detected_locale ) {
			if ( false === $lang = $this->get_cookie_lang() ) {
				return $locale;
			}

			if ( false === $loaded_locale = $this->get_locale_from_transient( $lang ) ) {
				$loaded_locale = $this->get_locales_from_db( $lang );
			}
			$this->detected_locale = $loaded_locale;
		}

		if ( false === $this->detected_locale ) {
			return $locale;
		}

		return $this->detected_locale;
	}

	/**
	 * Gettung users language by cookie
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function get_cookie_lang() {
		if ( array_key_exists( 'pll_language', $_COOKIE ) ) {
			return $_COOKIE['pll_language'];
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

		if ( ! is_array( $languages ) ) {
			return false;
		}

		foreach ( $languages AS $language ) {
			if ( $language['slug'] === $lang ) {
				return $language['locale'];
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
	private function get_locales_from_db( $lang ) {
		global $wpdb;

		$languages = $wpdb->get_col( "SELECT locale FROM {$wpdb->term_taxonomy} WHERE taxonomy='language'" );
		foreach ( $languages AS $language ) {
			$language = maybe_unserialize( $language );
			if ( $language['flag_code'] === $lang ) {
				return $language['locale'];
			}
		}

		return false;
	}
}


