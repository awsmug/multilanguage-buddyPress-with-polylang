<?php
/**
 * Class BPPL_User
 *
 * @since 1.0.0
 *
 * This class contains the functionalities to get user informations about languages
 */

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BPPL_User {
	/**
	 * User object
	 *
	 * @since 1.0.0
	 *
	 * @var WP_User
	 */
	private $user = false;

	/**
	 * BPPL_User constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $user_id = null ) {
		$this->set_user( $user_id );
	}

	/**
	 * Setting PLL language cookie
	 *
	 * We are setting and overwriting the cookie every time from DB,
	 * because we can have different users using one.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|int $user_id User id on success, false on error.
	 */
	public function set_user( $user_id = null) {
		if( ! empty( $user_id ) ) {
			if( false === $this->user = get_user_by('id', $user_id ) ) {
				return false;
			}
			return $this->user->ID;
		}

		if( false === $this->user = $this->get_logged_in_user() ) {
			return false;
		}

		// If we have no user, we do not set anything
		if( 0 === $this->user->ID ) {
			return false;
		}

		return $this->user->ID;
	}

	/**
	 * Saving user locale
	 *
	 * @param $locale
	 *
	 * @return int|WP_Error $user_id The updated user's ID or a WP_Error object if the user could not be updated.
	 */
	public function save_locale( $locale ) {
		if( false === $this->user ) {
			return new WP_Error( 'bppl_no_user_id_on_saving', 'Can not save user lang without user id.' );
		}

		return wp_update_user( array( 'ID' => $this->user->ID, 'locale' => $locale ) );
	}

	/**
	 * Returns current user locale
	 *
	 * @since 1.0.0
	 *
	 * @return string|WP_Error $locale
	 */
	public function get_locale() {
		if( false === $this->user ) {
			return new WP_Error( 'bppl_no_user_id_on_getting_locale', 'Can not get locale without user.' );
		}

		$locale = $this->user->locale;

		return $locale;
	}

	/**
	 * Returns current user language
	 *
	 * @since 1.0.0
	 *
	 * @return string|WP_Error $lang The lang slug of polylang
	 */
	public function get_lang() {
		if( false === $this->user ) {
			return new WP_Error( 'bppl_no_user_id_on_getting_lang', 'Can not get locale without user.' );
		}

		$locale = $this->user->locale;

		$lang = bppl()->polylang()->get_lang_slug_by_locale( $locale );

		return $lang;
	}

	/**
	 * Getting logged in User
	 *
	 * @since 1.0.0
	 *
	 * @return false|WP_User The User as object or false if nothing was found
	 */
	private function get_logged_in_user() {
		if( ! array_key_exists( $this->get_logged_in_cookie_name(), $_COOKIE ) ) {
			return false;
		}

		$cookie_content = $_COOKIE[ $this->get_logged_in_cookie_name() ];
		$cookie_content = explode( '|', $cookie_content );

		$userdata = WP_User::get_data_by( 'login', $cookie_content[ 0 ] );

		if ( ! $userdata ) {
			return false;
		}

		$user = new WP_User;
		$user->init( $userdata );

		return $user;
	}

	/**
	 * Get WordPress logged in cookie name
	 *
	 * This is used because we get in trouble with including userfunctions on Multisite
	 *
	 * @since 1.0.0
	 *
	 * @return string $logged_in_cookie The name of the cookie
	 */
	private function get_logged_in_cookie_name(){
		$siteurl = get_site_option( 'siteurl' );
		$cookie_hash = md5( $siteurl );
		$logged_in_cookie = 'wordpress_logged_in_' . $cookie_hash;

		return $logged_in_cookie;
	}
}