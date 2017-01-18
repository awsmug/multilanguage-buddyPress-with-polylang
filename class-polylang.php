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
	 * Adding Actionhooks & Co.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
        $this->init_polylang_languages();
        $this->set_language_cookie();
	}

	/**
     * Init Polylang languages
     *
	 * Initializing an array for the languages for later use.
	 *
	 * @since 1.0.0
	 */
	public function init_polylang_languages(){
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

			$this->languages[ $language->slug ] = array(
				'term_id' => $language->term_id,
				'name' => $language->name,
				'lang'  => $language->slug,
				'locale' => $description[ 'locale' ],
			);
		}
	}

    /**
     * Setting PLL language cookie
     *
     * We are setting and overwriting the cookie every time from DB,
     * because we can have different users using one.
     *
     * @since 1.0.0
     */
	private function set_language_cookie() {
        if( ! function_exists( 'wp_get_current_user' ) ) {
            require_once ABSPATH . 'wp-includes/pluggable.php';
        }
	    $user = wp_get_current_user();

	    // If we have no user, we do not set anything
	    if( 0 === $user->ID ) {
	        return;
        }

		$lang_slug = $this->get_default_lang();

        if ( property_exists( $user, 'locale ') ) {
	        $lang_slug = $this->get_lang_slug_by_locale( $user->locale );
        }

	    if( is_wp_error( $lang_slug ) ) {
		    bppl_messages()->add( $lang_slug->get_error_message() );
	        return;
        }

        setcookie( 'pll_language', $lang_slug );
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
     * Getting the user locale
     *
     * Gets the locale for the user from user settings.
     *
     * @since 1.0.0
     *
     * @param int $user_id WordPress user ID
     *
     * @return string|false $locale WordPress Locale (en_US, de_DE, fr_FR and so on). False if user was not found.
     */
    public function get_user_locale( $user_id ) {
        $user = get_userdata( $user_id );

        if( ! $user ) {
            return false;
        }

        return $user->locale;
    }

    /**
     * Setting the user locale
     *
     * Saves the user locale to the user settings
     *
     * @since 1.0.0
     *
     * @return bool|WP_Error $saved True if everything is saved fine or WP_Error on failure.
     */
    public function save_user_locale() {
        return true;
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
	 * @param string $locale Name of the locale
	 *
	 * @return string|WP_Error
	 */
	public function get_lang_slug_by_locale( $locale ) {
		$lang = $this->get_value_by( 'locale', $locale, 'lang' );
		return $lang;
	}
}