<?php
/**
 * Class BPPL_Polylang
 *
 * @since 1.0.0
 *
 * This class managaes all needed Polylang functionality for translating BuddyPress
 */

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BPPL_Polylang {
	/**
	 * All languages added by Polylang
	 *
	 * @since 1.0.0
     *
	 * @var array
	 */
	protected $languages = array();

	/**
	 * Language of user
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	protected $current_user_lang = null;

	/**
	 * User data
	 *
	 * @var BPPL_User
	 */
	protected $user;

	protected $switched;

	/**
	 * Adding Actionhooks & Co.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->user = new BPPL_User();

        $this->init_languages();
		$this->set_language_cookie();

		// Todo: Maybe change this, because it changes the user language on opening sites of other languages
        add_action( 'pll_language_defined', array( $this, 'define_language' ), 10, 2 );

        add_filter( 'option_polylang', array( $this , 'option_polylang' ), 10, 1 );

		add_filter( 'pll_model', array( $this, 'pll_model') );
		add_action( 'pll_init', array( $this, 'pll_init') );
	}

	public function option_polylang() {
		remove_filter( 'option_polylang', array( $this , 'option_polylang' ), 10, 1 );
		$option = get_blog_option( bp_get_root_blog_id(), 'polylang' );
		return $option;
	}

	public function pll_model( $model ) {
		if( ! bp_is_root_blog() ) {
			switch_to_blog( bp_get_root_blog_id() );
			$this->switched = true;
		}

		return $model;
	}

	public function pll_init() {
		if( $this->switched ) {
			$this->init_languages();
			restore_current_blog();
		}
	}

	/**
     * Init Polylang languages
     *
	 * Initializing an array for the languages for later use.
	 *
	 * @since 1.0.0
	 */
	public function init_languages(){
	    global $wpdb;

        /**
         * We have to work with our own SQL statements, because Polylang loads everything
         * at the plugins_loaded hook with priority 1. No chance to get in after taxonomies
         * loaded and to do anything like setting cookie and anything else on the Polylang start.
         */
        $languages = $wpdb->get_results( "SELECT * FROM {$wpdb->terms} AS t, {$wpdb->term_taxonomy} AS tt WHERE t.term_id = tt.term_id AND taxonomy = 'language'" );

		// Stopping if no languages existing
		if( null === $languages ) {
			bppl_messages()->add( $languages->get_error_message() );
			return;
		}

		foreach( $languages AS $language ) {
			$description = maybe_unserialize( $language->description );

			if( is_array( $description ) &&  array_key_exists( 'locale', $description )) {
				$locale = $description[ 'locale' ];

				$this->languages[ $language->slug ] = array(
					'term_id' => $language->term_id,
					'name' => $language->name,
					'lang'  => $language->slug,
					'locale' => $locale,
				);
			}
		}
	}

	/**
	 * Setting up language cookie of Polylang
	 *
	 * @since 1.0.0
	 */
    private function set_language_cookie() {
    	$locale = $this->user->get_locale();

    	if( empty( $locale ) ) {
    		return;
	    }

    	if( ! is_wp_error( $locale ) ) {
			$lang = $this->get_lang_slug_by_locale( $locale );
	    } else {
		    $lang = $this->get_default_lang();
	    }

	    if( is_wp_error( $lang ) ) {
			return $lang;
	    }

	    if( ! setcookie( 'pll_language', $lang ) ) {
	    	bppl_messages()->add( __( 'Could not set language cookie for Polylang', 'buddypress-polylang' ) );
	    }
    }

	/**
	 * Getting global polylang options
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name Name of the Polylang option
	 *
	 * @return bool|string
	 */
    public function get_option( $option_name ) {
    	$options = maybe_unserialize( get_option( 'polylang' ) );

    	if( ! array_key_exists( $option_name, $options ) ) {
    		return false;
	    }

    	return $options[ $option_name ];
    }

	/**
	 * Getting all available languages
	 *
	 * @since 1.0.0
	 *
	 * @return array|WP_Error $languages All language information in an array
	 */
	public function get_languages() {
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
			return new WP_Error( 'languages_not_existing', __( 'There are no languages added in Polylang yet.', 'buddypress-polylang' ) );
		}

		foreach( $languages AS $language ) {
			if( array_key_exists( $key, $language ) && $language[ $key ] === $value ) {
				return $language[ $return_key ];
			}
		}

		return new WP_Error( 'not_found', __( 'Value Not found.', 'buddypress-polylang' ) );
	}

	/**
	 * Getting a locale by language slug
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Lang slug
	 *
	 * @return string|WP_Error
	 */
	public function get_lang_slug_by_locale( $locale ) {
		$lang = $this->get_value_by( 'locale', $locale, 'lang' );
		return $lang;
	}

	/**
	 * Getting a language slug by locale
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Name of the locale
	 *
	 * @return string|WP_Error
	 */
	public function get_locale_by_lang_slug( $lang ) {
		$locale = $this->get_value_by( 'lang', $lang, 'locale' );
		return $locale;
	}

	/**
	 * Getting default language of Polylang
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_default_lang() {
		$default_lang = $this->get_option( 'default_lang' );

		/**
		 * Filter for default language
		 *
		 * @since 1.0.0
		 *
		 * @param string $default_language The requested field for the default language
		 */
		return apply_filters( 'bppl_default_language', $default_lang );
	}

	/**
	 * Getting user locale
	 *
	 * @since 1.0.0
	 *
	 * @param $user_id int User ID
	 *
	 * @return array|mixed|WP_Error
	 */
	public function get_user_locale( $user_id ) {
		$locale = get_user_meta( $user_id, 'locale', true );

		if( empty( $locale ) ) {
			$lang = bppl()->polylang()->get_default_lang();
			$locale = bppl()->polylang()->get_value_by('lang', $lang, 'locale');
		}

		return $locale;
	}

	/**
	 * Saving the locale for a user in WordPress user locale
	 *
	 * @since 1.0.0
	 *
	 * @param $user_id
	 * @param $locale
	 *
	 * @return int|WP_Error
	 */
	public function save_user_locale( $user_id, $locale ) {
		return wp_update_user( array( 'ID' => $user_id, 'locale' => $locale ) );
	}

	/**
	 * Setting the user locale
	 *
	 * Saves the user locale to the user settings
	 *
	 * @since 1.0.0
	 *
	 * @param string $lang_slug Language slug
	 * @param PLL_Language $current_lang Language object
	 *
	 * @return bool|WP_Error $saved True if everything is saved fine or WP_Error on failure.
	 */
	public function define_language( $lang_slug, $current_lang ) {
		$user = wp_get_current_user();

		// We do not save if the user has not logged in or he is in wp-admin
		if( 0 === $user->ID || is_admin() ) {
			return;
		}

		$this->save_user_locale( $user->ID, $current_lang->locale );
	}
}