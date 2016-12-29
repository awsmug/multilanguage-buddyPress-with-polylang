<?php

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BP_Polylang {
	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @var BP_Polylang $instance;
	 */
	protected static $instance;

	/**
	 * All languages added by Polylang
	 * @var array
	 */
	protected $languages = array();

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
	 * @return BP_Polylang $instance
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
		add_action( 'plugins_loaded', array( $this, 'init_polylang_languages' ) );
	}

	/**
	 * Initializing an array for the languages
	 *
	 * @since 1.0.0
	 */
	public function init_polylang_languages(){
		$languages = get_terms( array(
			                        'taxonomy' => 'language',
			                        'hide_empty' => false,
		                        ) );

		// Stopping if no languages existing
		if( is_wp_error( $languages ) ) {
			return;
		}

		foreach( $languages AS $language ) {
			$description = maybe_unserialize( $language->description );

			$this->languages[ $language->slug ] = array(
				'name' => $language->name,
				'lang'  => $language->slug,
				'locale' => $description[ 'locale' ],
				'term_id' => $language->term_id,
			);
		}
	}

	/**
	 * Getting all available languages
	 *
	 * @since 1.0.0
	 *
	 * @return array|WP_Error $languages All language information in an array
	 */
	public function get_languages() {
		if( ! did_action( 'plugins_loaded' ) ) {
			return new WP_Error( 'actionhook_not_passed', __( 'Actionhook "plugins_loaded" not passed. Please try at a later moment.', 'multilanguage-buddypress-with-polylang' ) );
		}

		return $this->languages;
	}

	/**
	 * Flexible getting values from array
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 * @param string $value
	 * @param string $return_key
	 *
	 * @return array|WP_Error
	 */
	public function get_value_by( $key, $value, $return_key ) {
		$languages = $this->get_languages();

		if( is_wp_error( $languages ) ) {
			return $languages;
		}

		if( count( $languages ) === 0 ) {
			return new WP_Error( 'languages_not_existing', __( 'There are no languages added in Polylang yet.', 'multilanguage-buddypress-with-polylang' ) );
		}

		foreach( $languages AS $language ) {
			if( array_key_exists( $key, $language ) && $language[ $key ] === $value ) {
				return $language[ $return_key ];
			}
		}

		return new WP_Error( 'not_found', __( 'Not found.', 'multilanguage-buddypress-with-polylang' ) );
	}

	/**
	 * Getting a locale by language slug
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Name of the locale
	 *
	 * @return array|WP_Error
	 */
	public function get_lang_by_locale( $locale ) {
		$lang = $this->get_value_by( 'locale', $locale, 'lang' );
		return $lang;
	}
}